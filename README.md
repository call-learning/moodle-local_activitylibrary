Activity Library local plugin
=============================

[![Moodle Plugin CI](https://github.com/call-learning/moodle-local_activitylibrary/actions/workflows/moodle-plugin-ci.yml/badge.svg)](https://github.com/call-learning/moodle-local_activitylibrary/actions/workflows/moodle-plugin-ci.yml)

`local_activitylibrary` adds an activity catalogue to Moodle. It lets administrators define
custom fields on course modules, expose those fields as filters, and present matching
activities through a dedicated catalogue page.

The plugin was developed for
[3114](https://3114.fr/3114-qui-sommes-nous/).
Its catalogue approach was inspired by
[Pedagothèque Numérique](https://www.imt.fr/formation/academie-transformations-educatives/ressources-pedagogiques/pedagotheque-numerique/),
which acts as a searchable catalogue of teaching and learning activities.

Overview
========

The plugin provides:

* a catalogue page at `/local/activitylibrary/index.php`
* activity-level custom fields managed through Moodle's custom field API
* filter widgets generated from those custom fields
* built-in filters for free text, activity type, course, and tags
* card and list views with user preferences for display, sorting, and pagination
* per-course and per-activity visibility controls for the catalogue

By default, catalogue results only include activities that are visible to the current user.
For non-admin users, the home catalogue is scoped to their enrolled/accessible courses.
Administrators can browse the full catalogue scope, except for courses explicitly hidden in
plugin settings.

Installation
============

1. Copy the plugin into `local/activitylibrary`.
2. Visit the Moodle notifications page and complete the upgrade.
3. Make sure the optional subsystem setting `Enable Activity Library` is enabled.

After installation, the plugin adds its administration pages under the Courses area when the
feature is enabled.

Navigation and Access
=====================

When enabled, the plugin adds a navigation entry pointing to the activity catalogue.
The label comes from the plugin language string `activitylibrary`, unless overridden by the
`menutextoverride` setting.

Relevant capabilities include:

* `local/activitylibrary:view`: view the activity library
* `local/activitylibrary:manage`: access plugin management pages
* `local/activitylibrary:editvalue`: edit activity library custom field values
* `local/activitylibrary:configurecustomfields`: configure activity library custom fields
* `local/activitylibrary:changelockedcustomfields`: change locked custom field values

Settings
========

Global feature toggle
---------------------

The plugin adds the `Enable Activity Library` checkbox to Moodle optional subsystems.
When disabled:

* the catalogue navigation entry is not added
* the plugin settings pages are hidden from the Courses administration section
* course module edit forms do not show the activity library controls

Plugin settings page
--------------------

When the feature is enabled, the main settings page is available from the Courses
administration area.

The following settings are currently available:

* `hiddencoursesid`
  Comma-separated course IDs to exclude from the catalogue entirely. Hidden courses are removed
  from the available scope before filtering and before result rendering.
* `menutextoverride`
  Optional multilingual menu text override for the navigation label. If empty, the standard
  plugin language string is used. The expected format is one line per language, for example:

```text
"Activity library"|en
"Bibliothèque d'activités"|fr
```

Custom fields
=============

The plugin provides a management page for activity custom fields. These fields are attached to
course modules and can be used to classify activities with metadata such as audience, modality,
duration, or internal taxonomies.

Each configured field can be exposed as a filter on the catalogue page. The filtering UI is
generated from the field definitions, so administrators do not need to implement separate form
controls for each new field.

Catalogue visibility
====================

Course-level visibility
-----------------------

Courses listed in `hiddencoursesid` are excluded from the catalogue for everyone, including
administrators.

Activity-level visibility
-------------------------

Each course module edit form contains an `Activity Library Parameters` section with a
`Hide from catalogue` checkbox.

When selected, the activity remains in the course but is excluded from the activity library.
This is stored separately from standard Moodle activity visibility, so it can be used as a
catalogue-specific hide/show control.

Usage
=====

Typical setup flow:

1. Enable the plugin in optional subsystems.
2. Create or review activity custom fields from the plugin management page.
3. Populate those custom fields on relevant activities.
4. Optionally hide specific courses with `hiddencoursesid`.
5. Optionally hide specific activities with `Hide from catalogue` on the activity edit form.
6. Open `/local/activitylibrary/index.php` and verify the generated filters and results.

The catalogue supports:

* free-text search on activity name and description metadata exposed by the plugin
* filtering by selected course
* filtering by Moodle activity module type
* filtering by configured tags
* filtering by plugin-managed custom field values
* sorting by title or last modification date
* pagination preferences persisted per user
* card or list display preferences persisted per user

Notes
=====

* The catalogue page is designed for Moodle users. If you want guest access, configure Moodle
  guest or auto-login behaviour separately at site level.
* Hidden catalogue courses can produce an intentionally empty result set for users whose scope
  only contains hidden courses.
* The plugin focuses on catalogue visibility, not on overriding Moodle enrolment or access
  permissions.

Authors
=======

Project initiated and produced by DP Pole IRM - Institut Mines-Télécom.

Implemented by Laurent David - SAS CALL Learning.
