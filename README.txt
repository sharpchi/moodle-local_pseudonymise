WORK IN PROGRESS
This is a tool to help pseudonymise site content for research purposes.

Pseudonymisation (https://en.wikipedia.org/wiki/Pseudonymization) differs from 
anonymisation in that identifying fields are replaced with human-readable artificial identifiers. Data
may also be altered slightly to further distance research data from source individuals.

TO DO: 

Data fields (e.g. dates, grades) will be altered by introducing a small amount of random variance 
(within 0.1 SD  of the data values), to protect user identities from inference attacks (not yet implemented).

Long text fields are still filled with random text matching the length of the original text. These will be replaced with "lorem ipsum" text of the same length.

WARNING: This will alter data throughout a whole site. DO NOT use on production sites.

To install this tool...
 1. Copy your site, including the code, database and data folder.
 2. Copy the 'pseudonymise' folder to the /local folder of your copied site (or clone from Github).
 3. Visit the Notifications page (Site administration -> Notifications).

The tool can then be accessed from:
 - The web interface: Site administration -> Development -> Pseudonymise (can result in a timeout on a large site)
 - Command line interface: local/anonymise/cli/pseudonymise.php

Note that you need to select 'Pseudonymise all' option to pseudonymise all site's data. Depending on what options you select not everything in the site is pseudonymised. Don't give your private data away.

Examples of generated names:

Courses (randomized):
Course Independent Study in Analysis of Musculoskeletal Employment and Forecasting

Sections (serialized):
Section Dialogue Qualifications and Folklore

Activities (serialized): 
Forum Web Organization and Citizenship
Page Abstract Beverage Syntax and Hospitality
File Abstract Dialogue Wealth and Mathematics
Assignment Yeast Vertebrates and Neuroscience
Wiki Rennaisance Nanoscience and Kant
URL Rennaisance Journalism and Jazz
Workshop Zebras and Xenophilia
IMS content package Zygotes
Quiz Yeast Vertebrates and Yersinia Pestis

Users:
Adriana Shkreli
Alejandro Abbasi
Benita Korhonen

Note: This plugin benefits from the use of Gravatar to generate unique avatar images based on the email address, e.g. using the robohash algorithm (https://robohash.org/). See https://docs.moodle.org/en/User_policies#Gravatar_default_image_URL for more details.
