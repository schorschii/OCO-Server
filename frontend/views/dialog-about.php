<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<div style='display:flex;align-items:center;margin-bottom:20px'>
	<img src='img/logo.dyn.svg' style='margin-right:15px'>
	<div style='display:inline-block'>
		<h3 style='margin-top:0px'><?php echo LANG['project_name']; ?></h3>
		<div><?php echo LANG['version'].' '.APP_VERSION; ?></div>
		<div><?php echo LANG['app_subtitle']; ?></div>
		<div><?php echo LANG['app_copyright']; ?></div>
	</div>
</div>

<h3>License</h3>
<p>
	This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
</p>
<p>
	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
</p>
<p>
	You should have received a copy of the GNU General Public License along with this program. If not, see https://www.gnu.org/licenses/.
</p>

<h3>3rd Party Components</h3>
<p>
	<a href='https://fonts.google.com/icons' target='_blank'><b>Material Icons</b></a>, Copyright (c) 2021 Google LLC, Apache License, Version 2.0<br>
	You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0
</p>
<p>
	<a href='https://github.com/catdad/canvas-confetti' target='_blank'><b>confetti.js</b></a>, Copyright (c) 2020, Kiril Vatev, ISC License<br>
	Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.
</p>
