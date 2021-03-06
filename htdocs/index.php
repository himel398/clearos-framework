<?php

/**
 * System time manager class.
 *
 * @category   Framework
 * @package    Application
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/framework/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

// TODO: figure out a better way to implement this quasi captive portal stuff.
if (($_SERVER['SERVER_PORT'] == 82) && file_exists('/usr/clearos/apps/web_proxy/htdocs/'))
    header("Location: http://" . $_SERVER['SERVER_ADDR'] . ":82/app/web_proxy/warning/configuration");
else if (file_exists('/usr/clearos/apps/base/htdocs/'))
    header("Location: /app/base/");
else
    echo 'These aren\'t the APIs you\'re looking for.  You can go about your business.  Move along... move along.';
