<?php
$SUBVIEW = 1;
require_once('../../lib/Loader.php');
require_once('../session.php');
?>

<div style='display:flex;align-items:center;margin-bottom:20px'>
	<img src='img/logo.dyn.svg' style='margin-right:15px'>
	<div style='display:inline-block'>
		<h3 style='margin-top:0px'><?php echo LANG['project_name']; ?></h3>
		<div><?php echo LANG['version'].' '.APP_VERSION.' '.APP_RELEASE; ?></div>
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
	<a href='https://github.com/erusev/parsedown' target='_blank'><b>Parsedown</b></a>, Copyright (c) 2013-2018 Emanuil Rusev, erusev.com, MIT License<br>
	Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
	<br><br>
	The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
	<br><br>
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
</p>
<p>
	<a href='https://github.com/catdad/canvas-confetti' target='_blank'><b>confetti.js</b></a>, Copyright (c) 2020, Kiril Vatev, ISC License<br>
	Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.
	<br><br>
	THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
</p>
