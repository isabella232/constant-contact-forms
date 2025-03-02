=== Constant Contact Forms ===
Contributors:      constantcontact, webdevstudios, tw2113, znowebdev, ggwicz, ravedev, oceas, dcooney
Tags: capture, contacts, constant contact, constant contact form, constant contact newsletter, constant contact official, contact forms, email, form, forms, marketing, mobile, newsletter, opt-in, plugin, signup, subscribe, subscription, widget
Requires at least: 5.2.0
Tested up to:      5.7.0
Stable tag:        1.12.0
License:           GPLv3
License URI:       http://www.gnu.org/licenses/gpl-3.0.html
Requires PHP:      5.6

The official Constant Contact plugin adds a contact form to your WordPress site to quickly capture information from visitors.

== Description ==

##Work smarter, not harder. The Constant Contact Way
Create branded emails, build a website, sell online, and make it easy for people to find you—all from one place.

https://www.youtube.com/watch?v=Qqb0_zcRKnM

**Constant Contact Forms** is the easiest way to connect your WordPress website with your Constant Contact account.

-  Effortlessly create sign-up forms to convert your site visitors into mailing list contacts.
-  Customize data fields, so you can tailor the type of information you collect from your users.
-  Captured email addresses will be automatically added to the Constant Contact email lists of your choosing.

**BONUS**: If you have a Constant Contact account, all new email addresses that you capture will be automatically added to the Constant Contact email lists of your choosing. Not a Constant Contact customer? Sign up for a [Free Trial](https://go.constantcontact.com/signup.jsp) right from the plugin.


##How To Get Started.

1. Signup for a [Free Trial](http://www.constantcontact.com/index?pn=miwordpress). ( Existing Constant Contact users can skip this step).
2. Follow [first-time setup instructions](https://knowledgebase.constantcontact.com/articles/KnowledgeBase/10054-WordPress-Integration-with-Constant-Contact).
3. [Create your first form](https://knowledgebase.constantcontact.com/articles/KnowledgeBase/18059-Create-a-Wordpress-Form?q=create%20a%20form%20wordpress&pnx=1&lang).
4. [Add a form anywhere on your website](https://knowledgebase.constantcontact.com/articles/KnowledgeBase/30850-Add-a-Form-Created-with-the-Constant-Contact-Plugin-to-a-WordPress-Page-or-Blog-Post?lang).
5. Watch as your visitors turn into lifetime contacts!

== Screenshots ==
1. Adding a New form when connected to Constant Contact account.
2. Viewing All Forms
3. Lists Page
4. Settings page
5. Basic Form

== Changelog ==

= 1.12.0 =
* Added: “Limit 500 Characters” description below textarea fields
* Added: CSS class selector to the div wrapping the list checkboxes
* Added: Force email notifications if no list is selected for a form
* Added: Multi-select list options to "advanced optin" settings
* Added: New setting to override default opt-in text
* Added: Two new filters to override state and zipcode labels
* Changed: Change <small> to <sub> for form disclaimer
* Fixed: Email field browser validation when form submits via AJAX
* Fixed: Erroneous placeholder attribute on submit button
* Fixed: Incomplete "ctct-label-" CSS class on submit button
* Updated: Addressed limits and issues regarding list management
* Updated: Better ensured security

== Frequently Asked Questions ==

#### Installation and Setup
[HELP: Install the Constant Contact Forms Plugin for WordPress to Gather Sign-Ups and Feedback](https://knowledgebase.constantcontact.com/articles/KnowledgeBase/10054-WordPress-Integration-with-Constant-Contact)

#### Constant Contact Forms Options
[HELP: Add email opt-in to a WordPress Form created with the Constant Contact plugin](http://knowledgebase.constantcontact.com/articles/KnowledgeBase/18260-WordPress-Constant-Contact-Forms-Options)

#### Frequently Asked Questions
[HELP: Enable Logging in the Constant Contact Forms for WordPress Plugin](https://knowledgebase.constantcontact.com/articles/KnowledgeBase/18491-Enable-Logging-in-the-Constant-Contact-Forms-for-WordPress-Plugin)

#### Constant Contact List Addition Issues
[HELP: Troubleshooting List Addition Issues in the Constant Contact Forms Plugin for WordPress](https://knowledgebase.constantcontact.com/articles/KnowledgeBase/18539-WordPress-Constant-Contact-List-Addition-Issues)

#### cURL error 60: SSL certificate problem
[HELP: WordPress cURL Error 60: SSL Certificate Problem](https://knowledgebase.constantcontact.com/articles/KnowledgeBase/18159-WordPress-Error-60)

#### Add Google reCAPTCHA to Constant Contact Forms
[HELP: Add Google reCAPTCHA to Your WordPress Sign-up Form to Prevent Spam Entries](http://knowledgebase.constantcontact.com/articles/KnowledgeBase/17880)

#### How do I include which custom fields labels are which custom field values in my Constant Contact Account?
You can add this to your active theme or custom plugin: `add_filter( 'constant_contact_include_custom_field_label', '__return_true' );`. Note: custom fields have a max length of 50 characters. Including the labels will subtract from the 50 character total available.

#### Which account level access is needed to connect my WordPress account to Constant Contact?
You will need to make the connection to Constant Contact using the credentials of the account owner. Campaign manager credentials will not have enough access.

### Error: Please select at least one list to subscribe to.
Some users are experiencing errors when upgrading from an older version of the plugin. If you are receiving an error "Please select at least one list to subscribe to" on your form submissions we recommend "Sync Lists with Constant Contact", this can be found in your admin dashboard Contact Form > Lists. If problem still persists we recommend recreating the form from scratch.


== Upgrade Notice ==
-  None