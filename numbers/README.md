Phone Number Normalization
==========================

For this example, normalize a `csv` list of phone numbers.

Starting Point
--------------
`normalize.php` takes `STDIN` and iterates over the rows. Each number is passed to Nexmo's Number Insight API to be
formatted for international and local use. The results are output to `SDTOUT`.

Goals
-----
- Allow the process to be resumed if interrupted.
- Allow making API requests in parallel.