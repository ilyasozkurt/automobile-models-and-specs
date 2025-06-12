# Automobile Manufacturers, Models, And Specs
A database which includes automobile manufacturers, models and engine options with specs.

## How to install and use Scrapper?

1. `git clone https://github.com/ilyasozkurt/automobile-models-and-specs && cd automobile-models-and-specs/scrapper`
1. `composer install`
3. Get a copy of `.env.example` and save it as `.env` after configuring database variables.
4. `php artisan migrate`
5. `php artisan scrape:automobiles`

## Data Information
* 124 Brand
* 7207 Model
* 30066~ Model Option (Engine)

### Brand Specs
* Name
* Logo

### Model Specs
* Brand
* Name
* Description
* Press Release
* Photos

### Engine Specs
* Name
* Engine -> Cylinders
* Engine -> Displacement
* Engine -> Power
* Engine -> Torque
* Engine -> Fuel System
* Engine -> Fuel
* Engine -> CO2 Emissions
* Performance -> Top Speed
* Performance -> Acceleration 0-62 Mph (0-100 kph)
* Fuel Economy -> City
* Fuel Economy -> Highway
* Fuel Economy -> Combined
* Drive Type
* Gearbox
* Brakes -> Front
* Brakes -> Rear
* Tire Size
* Dimensions -> Length
* Dimensions -> Width
* Dimensions -> Height
* Dimensions -> Front/rear Track
* Dimensions -> Wheelbase
* Dimensions -> Ground Clearance
* Dimensions -> Cargo Volume
* Dimensions -> Cd
* Weight -> Unladen
* Weight -> Gross Weight Limit

Data scrapped from autoevolution.com at **23/10/2024**

Sponsored by [offday.app](https://trustlocale.com "Discover the best days off to maximize your holiday!")
