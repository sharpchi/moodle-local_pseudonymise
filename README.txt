WORK IN PROGRESS
This is a tool to help pseudonymise site content for research purposes.

Pseudonymisation (https://en.wikipedia.org/wiki/Pseudonymization) differs from 
anonymisation in that identifying fields are replaced with human-readable artificial identifiers.

Data fields will also be altered by introducing a small amount of random variance (within 0.1 SD 
of the data values), to protect user identities from inference attacks (not yet implemented).

Also still to do: replacement of user names with common real-world given and family names.

WARNING: This will alter data throughout a whole site. DO NOT use on production sites.

To install this tool...
 1. Copy your site, including the code, database and data folder.
 2. Copy the 'pseudonymise' folder to the /local folder of your copied site (or clone from Github).
 3. Visit the Notifications page (Site administration -> Notifications).

The tool can then be accessed from:
 - The web interface: Site administration -> Development -> Pseudonymise.
 - Command line interface: local/anonymise/cli/pseudonymise.php

Note that you need to select 'Pseudonymise all' option to pseudonymise all site's data. Depending on what options you select not everything in the site is pseudonymised. Don't give your private data away.

