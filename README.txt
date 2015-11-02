Capability Explorer
===================

Capability Explorer is a tool to help explain how Moodle's capability system
works. Select a user, capability and context and you'll get a detailed
explanation of how that capability check is calculated by the system.

Minimum requirements
====================

Currently, due to the YUI treeview dependency, this plugin requires
Moodle 2.7+. It should be possible to backport to any 2.x version by including
the treeview library in the module.

Note: This plugin does not current support sites that use MNet.

Installation
============

Install this plugin into the admin/tool folder, in a subfolder called
capexplorer, then visit the Site Administration > Notifications page to
install.

You can then access the tool via:

Site Administration > Users > Permissions > Capability Explorer

For full documentation and screenshots, see:

http://docs.moodle.org/en/Capability_explorer

Bug reports
===========

Please submit any bug reports or feature suggestions via the Moodle tracker:

http://tracker.moodle.org/browse/CONTRIB

License
=======

Created by Simon Coggins.
License GNU GPL v3 or later. http://www.gnu.org/copyleft/gpl.html

