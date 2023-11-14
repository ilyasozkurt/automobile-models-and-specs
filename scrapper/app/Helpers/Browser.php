<?php

use HeadlessChromium\Browser;
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
        return getBrowser()->getPages()[0];
    }
}

if (!function_exists('destroyBrowser')) {
    /**
     * @return void
     * @throws Exception
     */
    function destroyBrowser(): void
    {
        Log::info('The browser instance is going to be destroyed.');
        getBrowser()->close();
        Log::info('The browser instance is destroyed.');
    }
}

if (!function_exists('browseUrl')) {
    /**
     * @param string $url
     * @return string
     * @throws Exception
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
     * @return string
     * @throws Throwable
     * @throws CommunicationException
     * @throws CannotReadResponse
     * @throws InvalidResponse
     * @throws ResponseHasError
     * @throws EvaluationFailed
     * @throws JavascriptException
     * @throws NavigationExpired
     * @throws NoResponseAvailable
     * @throws OperationTimedOut
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
