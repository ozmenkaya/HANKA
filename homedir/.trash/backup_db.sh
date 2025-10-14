#!/bin/bash
DATABASE="com_panelhankasys"
DATE=$(date +%Y-%m-%d_%H-%M-%S)
mysqldump -u 'com_ozmen' -p'=eTJHP2T.Z%i' 'com_panelhankasys' > "$DATABASE-$DATE.sql"