========
twitchat
========
## Purpose
Php hack to connect the Program-O chatbot to a twitter account.
This is a quick and dirty modification of the original script found [here.](http://www.rebelwords.org/2012/08/twitterbot-php-and-program-o/)

Credit should be given to Robert of [www.rebelworlds.com](http://www.rebelworlds.com) for figuring out 
the bulk of the steps for this procedure. Many thanks to Robert.

This script just attempts to "fix" some of the issues I ran into when I tried
to apply Robert's script on my chatbot.
 
Let me know if you make any useful modifications to this script. 
Robert of [www.rebelworlds.com](http://www.rebelworlds.com) may also apprecaite the source code to any future modifications.

#### Install instructions:
You can follow the instructions at [www.rebelworlds.com](http://www.rebelwords.org/2012/08/twitterbot-php-and-program-o/), 
but for your benefit here is the general gist of the requirements:


* Have a working installation of [Program-O](http://http://blog.program-o.com/)
* Intall the Abraham Williams' [OAuth PHP Library for Twitter.](https://github.com/abraham/twitteroauth/)
* Create a new Twitter application at [Twitter Developers](https://dev.twitter.com/), and create access tokens.
* Edit this script to fill in empty fields for database settings, twitter account, and OAuth credentials.
* Set a cron job to run this script (30 minute intervals advised).