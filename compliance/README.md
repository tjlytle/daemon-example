Site Compliance
===============

For this example, crawl a website looking for a keyword.

Starting Point
--------------
`cli.php` takes a `-u` url and `-k` keyword, and crawls any links at the _same domain_ looking for pages that contain
the keyword. Because this process may take some time, when a page is found the URL may be sent as an SMS alert to the 
phone number provided wth `-a`.

Goals
-----
- Back this process with a database so compliance issue can be tracked in the future.
- Ensure an error doesn't stall the crawl.
- Continuously monitor the process.