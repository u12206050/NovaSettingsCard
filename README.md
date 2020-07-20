
# Nova Settings Card


Nova card to add options to your website. To store data [akaunting/setting](https://github.com/akaunting/setting) is used.

You can attach the card to any [Nova Dashboard](https://nova.laravel.com/docs/2.0/customization/dashboards.html#default-dashboard). Can be used in multiple dashboard or in any resource.

The information is displayed in tabs.

If you want to use a KeyValue field use `resolveUsing() method to format the values:

```php
KeyValue::make('Meta')->resolveUsing(function ($value) {
	return json_decode($value);
})
```

![cover](https://user-images.githubusercontent.com/74367/68877274-17e05f00-0706-11ea-9690-2485ba896c41.png)

## Pre-install

Add repository to composer.json file:

```
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/u12206050/NovaSettingsCard.git",
            "no-api": true
        }
		]
```

## Instructions

1. Install Package
	```php
	composer require day4/nova-settings-card
	```

2. Publish [akaunting/setting](https://github.com/akaunting/setting) config and migrations

	```php
	php artisan vendor:publish --tag=setting
	```

3. Migrate settings table
	```php
	php artisan migrate
	```

4. Add SettingsCard to your own Nova Dashboard

	Available methods:

	* **fields** -> Tabbed nova fields
	* **name** -> Card name
	* **onSave($before, $after)** -> Callback function on save


	```php
	use Day4\SettingsCard\SettingsCard;


	/**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
	    return [
	        (new SettingsCard)->fields([
	            'General' => [
	                Text::make('Web Name'),
	                Boolean::make('Activated'),
	                Trix::make('Site Description'),
	                Image::make('Logo')->disk('s3'),
	                KeyValue::make('Meta')->resolveUsing(function ($value) {
	                    return json_decode($value);
	                }),
	            ],
	            'Scripts' => [
	                Code::make('Header Scripts')->language('javascript'),
	                Code::make('Footer Scripts')->language('javascript'),
	            ],
	            'Styles' => [
	                Code::make('Header Styles')->language('sass'),
	                Code::make('Footer Styles')->language('sass'),
	            ],
	        ])->name('My settings card'),
	    ];

        ...
    }
    ```
    You can set the name of the card with `name()` functions. Default to `Settings`.

## Localization

If you want to save separate settings for each language then do the following:

1. Add a `locale` column to the settings table either before migrating or manually afterward.

2. Add a middleware or use an existing one where you can call `setting()->setExtraColumns(['locale' => $locale]);` where `$locale` is the current language you want to save for.

3. Add `"locale"` to the `required_extra_columns` in the `settings.php` config file

```json
"Settings": "Opciones",
"Save settings": "Guardar opciones",
"Settings saved! - Reloading page.": "¡opciones guardadas! - Recargando la página..."
```
