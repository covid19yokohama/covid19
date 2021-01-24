#!/bin/bash

# update json. if update make update.txt
today=$(date "+%Y-%m-%d %H:%M");
echo "${today} php update...";

echo 'git pull';
git pull;
echo '';

php update.php

# if update.txt exist
if [[ -f ./update.txt ]]; then


	echo 'git add .';
	git add .;
	echo '';

	echo 'git tag -d ';
	git tag -d master-2020-02-04;
	git tag -d refs/heads/master;
	echo '';

	echo 'git merge -m ';
	git commit -m "auto update";
	echo '';

	echo 'git push';
	git push;
	echo '';

	echo "wait 5min for tweet...";
	sleep 300;

	php tweet.php

	rm update.txt
fi
