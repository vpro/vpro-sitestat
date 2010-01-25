<?php
/*
Plugin Name: VPRO Sitestat counter
Plugin URI:
Description: Deze plugin verzorgt een sitestat counter op je website.
Author: Hay Kranen / VPRO Digitaal
Version: 1.3
Author URI: http://www.vpro.nl/digitaal

Copyright 2009-2010 Omroepvereniging VPRO, afdeling Digitaal < http://www.vpro.nl/digitaal >

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once 'class-vpro-sitestat.php';

// Initialize the plugin
new VproSitestat;
?>