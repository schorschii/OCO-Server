from eralchemy import render_er

## draw schema from database
render_er('mysql://root:PASSWORD@localhost/oco2', 'dbschema.png')
