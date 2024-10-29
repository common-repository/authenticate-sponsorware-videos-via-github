=== Authenticate Sponsorware Videos via GitHub ===
Contributors:      opensheetmusicdisplay, fredmeister77, ranacseruet, jeremyhixon
Donate link:       https://OSMD.org/Donate
Tags:              block,github,video,sponsor,oauth
Requires at least: 5.5.0
Tested up to:      6.1.1
Stable tag:        1.2.2
Requires PHP:      7.0.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

This plugin allows Wordpress users to put a video and description behind Github oauth prompt. It can optionally check for sponsorship of a given organization or user to allow access.

== Description ==

With Github's new [Github Sponsors](https://github.com/sponsors/) program, Github developers can choose to sponsor open source projects.

This plugin is designed to help those wishing to monetize according to the 'sponsorware' pattern specified by Caleb Porzio:
https://calebporzio.com/i-just-hit-dollar-100000yr-on-github-sponsors-heres-how-i-did-it

Specifically it is to help with "Phase 3: Sponsored Screencasts".

This plugin utilizes Github OAuth and calls the Github API to gatekeep specified videos to only Github Users, with the option to require sponsorship to a particular organization or user.
The plugin utilizes the built-in HTML5 video player, by embedding (when the user is authorized) a video and source element.

The plugin adds an editor block to include in posts and a custom post type that specifies the necessary information for each video.

- The "Installation" section has information on setting up the plugin properly, as well as plugin-wide settings.
- The "FAQ" is empty currently. This will grow as we get actual questions from the community upon release.
- The "Creating an Authenticated Video" section covers adding a video that is behind Github authentication.
- The "Screenshots" section shows the admin section pages as well default examples of the video and auth screens.
- The "Limitations/Future Features" section contains info on some features that are desirable and their relative priority, as well as current limitations of the plugin.

#### About Us
We have developed the open-source [Opensheetmusicdisplay](https://opensheetmusicdisplay.org/): A library for rendering MusicXML in the browser using Vexflow.
We developed this plugin to help us more easily create sponsor-specific screencasts. 
We are making it available here free to give back to the sponsorware community and pay it forward.
We hope you find this plugin useful, and if so, please consider sponsoring us or donating at our link above.
Thank you!

== Installation ==

1. Install the plugin via the Wordpress Plugin installer. 
    1. In the Wordpress admin sidebar, navigate to Plugins -> Add New.
    2. *Wordpress Automatic Installation*
        1. Search for its listing near the top of this page: "Authenticate Sponsorware Videos via GitHub".
        2. Review the plugin information, reviews, details, etc.
        3. Click "Install Now" and Wordpress will automatically install it.
    2. **or** *Manual Upload*
        1. At the very top towards the left, select the "Upload Plugin" button. 
        2. Select the zip file for this plugin.
        3. Click "Install Now"
2. Activate the Plugin
    1. In the Wordpress admin sidebar, navigate to Plugins -> Installed Plugins
    2. Locate the "Authenticate Sponsorware Videos via GitHub" plugin in this list
    3. Select "Activate" beneath it
3. Specify the requisite settings
    1. A new entry will have appeared at the bottom of your admin sidebar: "Github Video" (you may need to refresh the page)
    2. Hover over this entry and select "Settings" from the submenu.
    3. Fill in the Client ID, Client Secret and Private Key settings, see "Settings Information" below (***these are required for the authentication to work***).

= Settings Information =

#### Github Setup
Since the plugin utilizes Github OAuth to verify users sponsorship status, you must setup a Github OAuth app. 
It is quite straightforward to do and Github has a good guide for it [here](https://docs.github.com/en/free-pro-team@latest/developers/apps/creating-an-oauth-app).

Make the application name and description whatever you like; Something that will tell users it's for your Github Sponsor videos.

The homepage URL will be the root URL of your Wordpress site where the videos will be. So let's say your site is at: `https://www.wordpress-example-url.com`
This is what you would enter in the Homepage URL field. (**NOTE: It is recommended to include the scheme, either http or https, of your server)

***IMPORTANT: READ OR PLUGIN WILL NOT WORK***
The Authorization callback URL *must* be a specific structure for the plugin to work correctly. You must append the query param: `?githubauthvideo_auth=2`
to your root url. So using our above example, the Authorization Callback URL should be:
`https://www.wordpress-example-url.com?githubauthvideo_auth=2`.

After you register the application, you can get the Client ID from the applications settings page, and also generate the Client Secret. (You should be taken to this page after register, but it can also be navigated to here: https://github.com/settings/developers) 

1. Copy and pase the Client ID from your Github OAuth application page to the "Github App Client ID" field in the plugin settings page (Wordpress).
2. Click "Generate a new client secret" on the Github OAuth application page to generate a new secret, and copy that as well into the "Github App Client Secret" field on the plugin settings page.

---

#### Private Key for Session Generation

Generate a random Base64 key for the Private Key For Session Generation field. This must be a base64 string, and should be long and random.
    > Use your favorite generator, or this one works (note: it is recommended to use in offline mode): https://generate.plus/en/base64. We require at least 32 characters.

This is used to cryptographically sign information (in a [JWT](https://jwt.io)) from the Wordpress server during Github Authentication.
This is an extra layer of security to ensure that no other application is attempting to forge access requests to the Wordpress server.

---

#### Optional Settings

The remaining settings are optional.
##### Ignore Sponsorship status

When this is enabled the script will merely check if the user is authenticated with Github to allow them to access videos, and will not check sponsorship status.

##### Do not Require HTTPS

Normally the plugin requires the server to have HTTPS enabled, and will not perform authentication without it. Check this to disable this requirement.
***It is highly recommended you use HTTPS on any production server. This setting should only be utilized in development environments***

##### Use Server-Side Rendering for Player

By default the plugin will render an HTML placeholder for Sponsor videos and check the sponsorship status client-side with Javascript to properly render the correct screen (or video).
This is to remain compatible with as many hosting providers as possible.

Some hosts, especially those for high performance hosting (e.g. Kinsta) will utilize response caching, making this client-side behavior necessary to properly render the videos.
If your host does not do this, you can toggle this setting to render server-side instead if prefered (essentially minor processing will occur on the server instead of client).

== Frequently Asked Questions ==

= Where can I get information or support for this plugin? =

Please reach out to us at: support@opensheetmusicdisplay.org if you have any issues or questions about the plugin.


== Screenshots ==

1. This is the unauthenticated view of an embedded video with the default WP theme.

2. This is the "Become a Sponsor Now" view of an embedded video with the default WP theme.

3. This is an embedded video rendered fully to a authenticated, sponsoring user.

4. This is the main settings page of the plugin. (admin)

5. This is the Authenticated Video listing page (admin)

6. This is an individual Authenticated Video editing page (admin)

7. This is the block editor for a post, showing the plugin's block being used (admin)

== Changelog ==

= 1.2.2 =
*Testing on 6.1.1, update to the latest npm packages for security fixes, etc.

= 1.2.1 =
* Further fixes for proxy issues. *

= 1.2.0 =
* Fix proxy issues - Properly stream videos from a specified host.

= 1.1.2 =
* If no video is specified, still render the text description.
* Allow iframe embeds in text description

= 1.1.1 =
* Fix issue with Github token changes.
We no longer try to sanity check github tokens since they can (and did) change in structure.

= 1.1.0 =
* Initial Release

== Creating an Authenticated Video ==

There are two broad steps to creating an authenticated video, covered here.

### Creating the Video
    1. Navigate to Github Video -> All Github Sponsor Videos from the admin dashboard sidebar.
    2. Select the "Add Github Sponsor Video"  button at the top of this page
    3. Fill out the information for the post (covered in detail below)
    4. Click Publish

#### Sponsor Video Fields

The video title will be rendered above the embedded video itself.

##### Media ID or Video Location URI
Specifies the actual video file. You can select "Upload" to the right to pick a video from the media library (or upload one), and it will automatically fill out the ID upon selection.
You can also enter a URL (e.g. http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4)
or even file URI for a locally stored file (e.g. file:///var/www-content/video.mp4)

_NOTE_
It is highly recommended to not use the 'http', URL hosted version of locally stored media; You should instead prefer to use the Wordpress media ID or the file:// schema.
This is because the video gatekeeping script will read and serve locally stored files directly, whereas for any URL hosted files it will read them as a web-client would, potentially using up your server bandwidth.
If it's being hosted on a seperate CDN or server where that isn't an issue, then have at it.

##### Github Organization or User Login
This is the organization or user 'login' from github to check against. This appears as the slug in the URL, e.g.: https://github.com/**opensheetmusicdisplay**/ 

This will also be the slug used when displaying the "Become a Sponsor Now" message to the user if they are not currently a sponsor but are authenticated with github, e.g.: https://github.com/sponsors/opensheetmusicdisplay

##### Splash Screen
This is another media field which can accept a URI (file or http/s) or a Wordpress media item can be selected.
This is the image that is rendered as the background for the placeholder/dummy video when the user is not authenticated (or is not a sponsor) and it is also set as the 'poster' for the video.

##### Unauthenticated Video Description
This is a rich text editor field and will be rendered underneath the video placeholder as the description when a user is not authenticated or is not a sponsor.

##### Authenticated Video Description
This is the same as the previous field, but is rendered when the user is authenticated and permitted to view the video.

---

### Including the Video in a Post
The video post type can be thought of as only a data mapping and will not render as a user facing post.
It must then be included in an actual Wordpress post or page to show up.

1. Navigate to Posts -> Add New from the admin dashboard sidebar
2. Select the '+' icon within the editor to choose a block
3. Select the "Github Authenticated Video" block.
4. With the block selected in the editor, use the "Select Video" dropdown on the right to select your previously created video.

You should see a mock-up of your video now rendered in the editor, with the title, splash screen and description.
If you save and publish this, the video should now render appropriately (either login splash screen, sponsor message, or actual video) on the front-end.

== Limitations/Future Features ==
This section is to briefly address known limitations and features that are either planned, or we'd like to see.
NOTE: None of these are guaranteed. We will work on them as we can.

##### Limitations
- We do not specify any JS library to render the video, so older browsers that don't support HTML5 will not work out of the box. At the present, you can use a library like [videojs](https://videojs.com/) to render on the video element. So this seems best left as a choice for plugin users.
- We utilize very minimal CSS styling on the video block, instead preferring to leave it up to the theme/site styling.

##### Possible Future or Premium Features
- Specifying a sponsorship tier per-video. Asterisk (*) will indicate any tier. 
- Size attribute for the video block. Currently we leave it up to CSS, but it might be nice to have the basic Wordpress size dropdown.
- Nice-to-have: More tightly integrating Github API for the Organization (or User) slug field; It would be nice to be able to have some sort of typeahead or selection here.
- Nice-to-have: Same typeahead/selection for the Sponsorship Tier.
- Nice-to-have: Include auto-generation option for the Private Session Key field.