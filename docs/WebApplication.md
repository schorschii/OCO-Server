# OCO: Web Application
The OCO web frontend allows you to manage computers & packages and view their details and relationships.

## Force Remove
When you try to remove a computer/package group with subgroups or a computer/package with pending jobs, the web frontend will abort the action and tell you that you first need to remove the subgroups/pending jobs.

You can force the deletion of the object by holding the shift key pressed when clicking on the "Remove" button. This will automatically delete all subgroups/pending jobs.

## Message Of The Day (MOTD)
The message of the day is displayed on the OCO homepage and can be modified in the `setting` table (setting entry with name `motd`).

Suggestions for your MOTD:
```
I know what you did steve.
```
```
WARNING: The consumption of alcohol may lead you to think people are laughing WITH you.
```
```
🌴 Yes, we can UTF8! 🌈
```
You can also insert some useful links (the MOTD is intentionally not HTML escaped).
```
WARNING: This device may contain Internet
<br><a href="/phpmyadmin" target="_blank">phpMyAdmin</a> ‧ <a href="https://bongo.cat/" target="_blank">BongoCat</a>
```

## Customization
You can customize the web design by creating the file `/frontend/css/custom.css` with your desired CSS rules inside. This ensures that your custom CSS is not overwritten with an update.

Why?
- to adapt your corporate design
- to be able to easily distinguish a test system from the production system

Example:
```
/* construction site header for test systems */
#header {
	background-image: repeating-linear-gradient(45deg, yellow, yellow 20px, black 20px, black 40px);
	font-weight: bold;
	text-shadow: 0px 0px 2px black, 0px 0px 2px black;
}
#login-bg {
	background-image: url('custombg.jpg');
}
```

## Client Commands / Client Extension
Please refer to Computers.md
