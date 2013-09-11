ytdl - YouTube(r) videos downloader written in php
====

A simple php script that takes a video url and displays the links to video files in all formats that YouTube offers for that video.

The script is installed at a web server, takes user input submitted via a web form, communicates to YouTube and decrypts its response. When downloading actual videos, the file is first downloaded to the web server, and only then sent to the client. This is because the IP address from which the video is downloaded should math that from which the video info was requested. So this script can cause a high traffic to your web server - keep in mind.

Note
====

Please note that it is against YouTube's [Terms of Service](https://developers.google.com/youtube/terms) to download and save the videos from their service. However, some of the videos there
have [CC BY license](http://www.youtube.com/yt/copyright/creative-commons.html), which means anyone can download and re-use them in their creations, mentioning the original author in the credits.

2013-09-11
Trento, Italy.
Sergei Sokolov.
