# Age Confirmation
## Synopsis
This extension aims to create an overlay on first visit of the wiki with a message.
The primarily function is a simple age confirmation. 

This is **not** an official extension provided and hosted by [Wikimedia](https://www.wikimedia.org).

## Original Code
This repository and - per definition - this extension is based on [CookieWarning](https://github.com/wikimedia/mediawiki-extensions-CookieWarning).
It is an adaption of the code based on the MIT License.

## Installation
1. Download or `git clone` this repository in a directory called `AgeConfirmation` into your `extensions/` folder.
2. Add the following code into your LocalSettings.php:
```

  wfLoadExtension ( 'AgeConfirmation' );

```
3. Navigate to "{Your Wiki Domain}/wiki/Special:Version" on your wiki to verify that the extension was successfully installed.

## Licensing
The code is based on the MIT License. Please refer to the [LICENSE](https://github.com/thanathros/mediawiki-extensions-ageconfirmation/blob/main/License) for further information.

## Contribute
Simply fork this repository, commit your changes and pull request them.
