HardcoreWP Spam Blacklist
==============

_HardcoreWP Spam Blacklist_ provides aggressive [blacklist](http://en.wikipedia.org/wiki/Blacklist_(computing) functionality combined with [whitelists](http://en.wikipedia.org/wiki/Whitelist) by **blocking** comments _(rather than marking them as spam)_, and it works in conjunction with other plugins like [Akismet](http://akismet.com/) that tag comments as spam.

##Upon Plugin Activation
Upon activation this plugin creates a post type (`'hcwp_spam_blacklist'`) to contain a set of spam _"control lists"_ for the various blacklists and whitelists it uses and it adds those to the admin menu underneath the Settings menu. The various control lists for each of the two (2) list types currently are:
- author
- IP
- email, and 
- URL 

After creating the post type HardcoreWP Spam Blacklist scans all the existing comments marked as  spam, and for each type author, IP, email and URL that occurs more than 3 times it adds to the associated blacklist. 

##Upon Comment Submission
When a comment is submitted containing _any_ blacklisted value in the associated field it displays a page explaining to the user they have been blacklisted and asks them to email the site admin with the details shown in an HTML `<textarea>` so they can be whitelisted. It also presents their comment in another `<textarea>` that they can copy and save it locally to post later. 

##Upon Marking Comments
Nothing yet happens when the user marks comments as approved or spam.

##Future Plans
In the next version it will initialize the whitelists with all approved posters and then beyond that we plan to add functionality that would maintain the list automatically whenever new comments are approved, tagged as spam or the spam list is emptied.

##Future Concerns
At the time of this writing we realize the whitelist will allow savvy spammers to bypass our blacklist simply by impersonating approved commenters so we are currently thinking about how to secure it while still enabling whitelist functionality. 

We might have to place a cookie on first comment so the cookie cab travel with the approved commenter's info and then the same combination of the `author+email+URL+cookie` can be whitelisted for the future.

We also might simply have to avoid the whitelists and allow other spam plugins to handle that aspect. If anyone has ideas, let us know [in the issues section](https://github.com/hardcorewp/spam-blacklist/issues).

##License
GPLv2

##Pull Requests
Pull requests **are very much encouraged**, although our ability to commit in a timely fashion will be pursuant to then-current demands of my client projects. 

In other words, we are very interested in receiving pull requests but if we're exceptionally busy at the time it might take a few days or even weeks to review before we commit.