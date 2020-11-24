# OCO: Web Application
The OCO web frontend allows you to manage computers & packages and view their details and relationships.

## Customization
You can customize the web design by creating the file `/frontend/css/custom.css` with your desired CSS rules inside. This ensures that your custom CSS is not overwritten with an update.

Why?
- to adapt your corporate design
- to be able to distinguish a test system from the production system

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
