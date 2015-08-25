## Introduction
This plugin enables you to issue dynamic, digital certificates using the [Accredible](https://accredible.com) API on your Moodle instance. They act as a replacement for the PDF certificates normally generated for your courses. An example output certificate can be viewed at: [https://accredible.com/example](https://accredible.com/example).

## Compatability

This plugin is currently in **Beta**. It was developed for **Moodle versions 27 and greater**.

---

##### Note - Not yet compatible with the latest API release
We have not updated the plugin to use **our more advanced certificate designs** yet. You can still issue certificates with advanced designs in bulk using the Accredible Dashboard. Further Moodle support will be added later this year.

## Installation

There are two installation methods that are available. Follow one of these, then log into your Moodle site as an administrator and visit the notifications page to complete the install.

#### Git

If you have git installed, simply visit the Moodle /mod directory and clone this repo:

    git clone https://github.com/accredible/moodle-mod_accredible.git accredible

#### Download the zip

1. Visit https://github.com/accredible/moodle-mod_accredible and download the zip. 
2. Extract the zip file's contents and **rename it 'accredible'**. You have to rename it for the plugin to work.
3. Place the folder in your /mod folder, inside your Moodle directory.

#### Get your API key

Make sure you have your API key from Accredible. We should have shared it with you, but it's also on the [API Management Dashboard](https://accredible.com/issuer/dashboard).

#### Continue Moodle set up

Start by installing the new plugin (go to Site Administration > Notifications if your Moodle doesn't ask you to install automatically).

![alt text][install-image]
[install-image]: https://s3.amazonaws.com/accredible-moodle-instructions/install_plugin.png "Installing the plugin"

After clicking 'Upgrade Moodle database now', this is when you'll enter your API key from Accredible.

![alt text][api-image]
[api-image]: https://s3.amazonaws.com/accredible-moodle-instructions/set_api_key.png "Enter your Accredible API key"

## Creating a Certificate

#### Add an Activity

Go to the course you want to issue certificates for and add an Accredible Certificates activity. 

![alt text][activity-image]
[activity-image]: https://s3.amazonaws.com/accredible-moodle-instructions/choose_activity.png "Add an Accredible Certificates Activity"

Issuing a certificate is easy - choose from 3 issuing options:

- Pick student names and manually issue certificates. Only students that need a certificates have a checkbox.
- Choose the Quiz Activity that represents the **final exam**, and set a minimum grade requirement. Certificates will get issued as soon as the student receives a grade above the threshold.
- Choose multiple Activities that need to be **completed** (attempted) for a student to receive their certificate.

![alt text][settings-image]
[settings-image]: https://s3.amazonaws.com/accredible-moodle-instructions/activity_settings.png "Choose how to issue certificates"

*Note: if you set both types of auto-issue criteria, completing either will issue a certificate.*

Once you've issued the certificate, head over to Accredible to edit the appearance.

Contact us at support@accredible.com if you have issues.

## FAQs

#### Why is nothing showing up? I can't see a certificate.

A certificate isn't created until you've either manually created one or had a student go through the criteria you set on the activity. For example if you select some required activities then a certificate won't be created until an enrolled student has completed them. Completing an activity or quiz as a course admin won't create a certificate.
