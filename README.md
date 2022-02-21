## [Portfolio Version README] FieldManager-to-CMB2 Data Converter

Takes existing legacy data stored via 'FieldManager' custom field plugin, and coverts it into a format that is compatible with the more-mainstream 'Custom Metabox 2' plugin.

Historically, the National University webdev team has used [FieldManager](https://fieldmanager.org/) for it's custom field management within WordPress. This was a decision that was made years ago, and all of our custom themes & plugins across more than a dozen sites were built upon this.

The decision was recently made to migrate our sites to [CMB2](https://github.com/CMB2/CMB2) instead.

The problem with this is that all of our sites have a large volume of legacy content that has been stored via FieldManager over the course of multiple years. There are some notable instances where FieldManager and CMB2 save data in different formats, which would cause lots of content to disappear.

A simple example is how both plugins handle a 'checkbox' custom field:
- Fieldmanager saves it to the database as either '1' (true) or blank (false)
- CMB2 saves it to the database as either 'on' (true) or it deletes the row altogether if false.

So for the above example, this plugin will find all existing custom 'checkbox' fields managed by FieldManager, and then convert '1' to 'on' or delete the row altogheter if false.