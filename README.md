## About this fork

This is a fork of the official PHP wrapper for the ActiveCampaign API. It differs from the original in the sense that this fork is composer aware, uses namespaces and makes it easy to use the API wrapper in a Symfony2 project.
To use the Acme bunle like the official Symfony2 cookbook, here's an example of how to use this repo:

- In you bundle dir (src/AcmeBunle), create/edit the services.yml file (AcmeBundle/Resources/config/services.yml):

```yml
parameters:
    ac:
        url: http://your.activecampaign.url
        apiKey: yourACkeyHash
        apiUser: userName
        apiPass: OptionalPass(hash)
        output: json (default output, can be changed on the fly)
```

- Then set up the service that will be accessing the api in such a way that it will be passed these params:

```yml
services:
    your_api_service:
        class: "%theClass%"
        calls:
            - [setActiveCampaignConfig, [%ac%]]
```

- When that's done, add the corresponding properties and methods to the service class in question

```php
<?php
namespace AcmeBundle\Service;
use AC\Arguments\Config,
    AC\ActiveCampaign;
/**
 * Your service class doc-block
 */
class YourService
{
    /**
     * @var \AC\ActiveCampaign
     */
    protected $api = null;

    /**
     * @var \AC\Arguments\Config
     */
    protected $config = null;

    /**
     * Is called by service locator
     * @param array $config
     * @return \AcmeBundle\Service\YourService
     */
    public function setActiveCampaignConfig(array $config)
    {
        $this->config = new Config($config);
        return $this;
    }

    public function getActiveCampaignConfig()
    {
        return $this->config;
    }

    /**
     * Lazy-load the api interface
     * @return \AC\ActiveCampaign
     */
    public function getActiveCampaignAPI()
    {
        if ($this->api === null)
        {
            $this->api = new ActiveCampaign(
                $this->getActiveCampaignConfig()
            );
        }
        return $this->api;
    }

}
```
- After this, you're all set to use the api in much the same way the examples.php file uses it:

```php
/**
 * Example method, perhaps to add to the ficticious class listed above
 * @param array $contact
 * @return \stdClass
 **/
public function syncContact(array $contact)
{
    //returns existing ActiveCampaign instance, or creates one if required
    $api = $this->getActiveCampaignApi();
    return $api->api('contact/sync', $contact);
}
```
Of course, you're free to expand on this by, for example, creating a `Contact` class, and distil from that a correctly formatted array that the API can work with.
Either way, this is how this API is to be used

This is the official PHP wrapper for the ActiveCampaign API. The purpose of these files is to provide a simple interface to the ActiveCampaign API. You are **not** required to use these files (in order to use the ActiveCampaign API), but it's recommended for a few reasons:

1. It's a lot easier to get set up and use (as opposed to coding everything from scratch on your own).
2. It's fully supported by ActiveCampaign, meaning we fix any issues immediately, as well as continually improve the wrapper as the software changes and evolves.
3. It's often the standard approach for demonstrating API requests when using ActiveCampaign support.

Both customers of our hosted platform and On-Site edition can use these files. On-Site customers should clone the source and switch to the <a href="https://github.com/ActiveCampaign/activecampaign-api-php/tree/onsite">"onsite" branch</a>, as that is geared towards the On-Site edition. Many features of the hosted platform are not available in the On-Site edition.

## Installation

You can install **activecampaign-api-php** by downloading or cloning the source.

[Click here to download the source (.zip)](https://github.com/ActiveCampaign/activecampaign-api-php/zipball/master) which includes all dependencies.

`require_once("includes/ActiveCampaign.class.php");`

Fill in your URL and API Key in the `includes/config.php` file, and you are good to go!

## Example Usage

### includes/config.php

<pre>
define("ACTIVECAMPAIGN_URL", "https://ACCOUNT.api-us1.com");
define("ACTIVECAMPAIGN_API_KEY", "njasdf89hy...23ad7");
</pre>

### examples.php

<pre>
require_once("includes/ActiveCampaign.class.php");

$ac = new ActiveCampaign(ACTIVECAMPAIGN_URL, ACTIVECAMPAIGN_API_KEY);

$account = $ac->api("account/view");
</pre>

Or just include everything in the same PHP file:

<pre>
define("ACTIVECAMPAIGN_URL", "https://ACCOUNT.api-us1.com");
define("ACTIVECAMPAIGN_API_KEY", "njasdf89hy...23ad7");
require_once("includes/ActiveCampaign.class.php");
$ac = new ActiveCampaign(ACTIVECAMPAIGN_URL, ACTIVECAMPAIGN_API_KEY);

$account = $ac->api("account/view");
</pre>

See our [examples file](https://github.com/ActiveCampaign/activecampaign-api-php/blob/master/examples.php) for more in-depth samples.

## Full Documentation

[Click here to view our full API documentation.](http://activecampaign.com/api)

## Reporting Issues

We'd love to help if you have questions or problems. Report issues using the [Github Issue Tracker](https://github.com/ActiveCampaign/activecampaign-api-php/issues) or email help@activecampaign.com.
