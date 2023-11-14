<?php

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\CommunicationException\CannotReadResponse;
use HeadlessChromium\Exception\CommunicationException\InvalidResponse;
use HeadlessChromium\Exception\CommunicationException\ResponseHasError;
use HeadlessChromium\Exception\EvaluationFailed;
use HeadlessChromium\Exception\JavascriptException;
use HeadlessChromium\Exception\NavigationExpired;
use HeadlessChromium\Exception\NoResponseAvailable;
use HeadlessChromium\Exception\OperationTimedOut;
use HeadlessChromium\Page;
use Illuminate\Support\Facades\Log;

if (!function_exists('createBrowser')) {
    function createBrowser(): Browser
    {

        $browserFactory = new BrowserFactory();

        $browser = $browserFactory->createBrowser([
            'headless' => true,
            'sandbox' => false,
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
            'windowSize' => [1440, 900],
            'keepAlive' => true,
            'imagesEnabled' => true,
            'ignoreCertificateErrors' => true,
            'userDataDir' => storage_path('browser'),
            'userCrashDumpsDir' => storage_path('browser/crash-dumps'),
            'customFlags' => [
                '--disable-blink-features',
                '--disable-blink-features=AutomationControlled',
                '--incognito',
                '--enable-automation=false',
                '--no-sandbox',
            ],
        ]);

        $browser->createPage();

        app()->instance(Browser::class, $browser);

        return app(Browser::class);

    }
}

if (!function_exists('destroyBrowser')) {
    /**
     * @return void
     * @throws Exception
     */
    function destroyBrowser(): void
    {
        getBrowser()->close();
    }
}

if (!function_exists('getBrowser')) {
    function getBrowser(): Browser
    {
        return app(Browser::class);
    }
}

if (!function_exists('getBrowserTab')) {
    /**
     * @return Page
     */
    function getBrowserTab(): Page
    {
        return getBrowser()->getPages()[0] ?? getBrowser()->createPage();
    }
}

if (!function_exists('renewBrowser')) {
    /**
     * @return void
     * @throws Exception
     */
    function renewBrowser(): void
    {
        destroyBrowser();
        createBrowser();
    }
}

if (!function_exists('browseUrl')) {
    /**
     * @param string $url
     * @param int $retryCount
     * @return string
     * @throws CannotReadResponse
     * @throws CommunicationException
     * @throws EvaluationFailed
     * @throws InvalidResponse
     * @throws JavascriptException
     * @throws NavigationExpired
     * @throws NoResponseAvailable
     * @throws OperationTimedOut
     * @throws ResponseHasError
     * @throws Throwable
     */
    function browseUrl(string $url, int $retryCount = 3): string
    {

        try {

            $browserTab = getBrowserTab();

            $browserTab->navigate($url)
                ->waitForNavigation();

            return $browserTab->evaluate('document.documentElement.outerHTML')
                ->getReturnValue();

        } catch (OperationTimedOut $exception) {

            renewBrowser();

            return browseUrl($url, $retryCount - 1);

        } catch (Throwable $th) {

            if ($retryCount > 0) {
                return browseUrl($url, $retryCount - 1);
            }

            Log::error('browseUrl: ' . $url, [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw $th;

        }

    }

}

if (!function_exists('browseUrlPost')) {
    /**
     * @param string $url
     * @param array $postData
     * @param int $retryCount
     * @return string
     * @throws CannotReadResponse
     * @throws CommunicationException
     * @throws EvaluationFailed
     * @throws InvalidResponse
     * @throws JavascriptException
     * @throws NavigationExpired
     * @throws NoResponseAvailable
     * @throws OperationTimedOut
     * @throws ResponseHasError
     * @throws Throwable
     */
    function browseUrlPost(string $url, array $postData = [], int $retryCount = 3): string
    {

        try {

            $browserTab = getBrowserTab();

            $browserTab->navigate('about:blank')
                ->waitForNavigation();

            $jsFunction = "function createAndSubmitForm(actionUrl, formData) {
                              // Create a form element
                              const form = document.createElement('form');
                              form.setAttribute('method', 'post');
                              form.setAttribute('action', actionUrl);

                              // Iterate through the form data and create input elements
                              for (const key in formData) {
                                if (formData.hasOwnProperty(key)) {
                                  const input = document.createElement('input');
                                  input.setAttribute('type', 'hidden');
                                  input.setAttribute('name', key);
                                  input.setAttribute('value', formData[key]);
                                  form.appendChild(input);
                                }
                              }

                              // Append the form to the document body
                              document.body.appendChild(form);

                              // Submit the form
                              form.submit();

                            }";

            $browserTab->evaluate($jsFunction);

            $browserTab->evaluate("createAndSubmitForm('$url', " . json_encode($postData) . ")");

            $browserTab->waitForReload($browserTab::DOM_CONTENT_LOADED);

            return $browserTab->evaluate('document.documentElement.outerHTML')
                ->getReturnValue();

        } catch (OperationTimedOut $exception) {

            renewBrowser();

            return browseUrl($url, $retryCount - 1);

        } catch (Throwable $th) {

            if ($retryCount > 0) {
                return browseUrlPost($url, $postData, $retryCount - 1);
            }

            Log::error('browseUrlPost: ' . $url, [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
                'postData' => $postData,
            ]);

            throw $th;

        }

    }

}
