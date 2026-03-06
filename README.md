Activity Library Local plugin
=============================

[![Build Status](https://travis-ci.org/call-learning/moodle-local_activitylibrary.svg?branch=main)](https://travis-ci.org/call-learning/moodle-local_activitylibrary)

This plugin adds new custom fields (using the customfield API introduced in Moodle 3.7) to courses and activities so they can be searched and classified.
These custom fields are then used to filter courses and activities on a catalog page.

The plugin was developed for Institut Mines Telecom and its [Pedagothèque Numérique](https://www.imt.fr/formation/academie-transformations-educatives/ressources-pedagogiques/pedagotheque-numerique/),
a course, teaching and learning activities catalog.

Installation
============

Add the plugin code to Moodle's `local` folder and run the update/upgrade process. You should then see a new plugin and new menus under `Administration > Courses`.

Usage
=====

The plugin adds a menu under `Administration > Courses`, where you can add a new type of custom field for activities:
* custom fields for activities

Those custom fields are then used on the activity library page, and each field gets a corresponding search form input.

The plugin will also add a new navigation menu called "Resources Library" that will list
all available courses. If you need to make this page accessible to non-logged-in users, make sure
you set "autologin" to on in `Administration > Site administration > Users > Permissions > User policies`
(see [Auto Login Guest](https://docs.moodle.org/39/en/Guest_access)). Otherwise, a login prompt will appear before viewing the page.

If you need to hide courses regardless of course visibility status, you can do so by adding course IDs
to the `hiddencoursesid` setting. Those courses will not appear in the activity library.

This is a temporary solution while a more generic one is explored, such as hiding courses by category, tag, or other criteria.
 
Authors
=======
Project initiated and produced by DP Pole IRM - Institut Mines-Télécom.
Implemented by Laurent David - SAS CALL Learning

TODO
====
 * Allow ordering of activities by last modification date
 * Add more information on the thumbnails
 * Check visibility of courses and activities
 * Add tags to courses and module to mark them as invisible on the activity library
 





 
