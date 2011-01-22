<?php

for($i=0;$i<256;$i++) {
	echo "CREATE TABLE IF NOT EXISTS `accounts$i` ("
		."`accountId` int unsigned not null auto_increment, "
		."`user` varchar(255), "
		."`storageNode` varchar(255), "
		."`app` varchar(255), "
		."`registrationToken` varchar(255), "
		."`state` int, "
		."`md5Pass` varchar(255), "
		."PRIMARY KEY (`accountId`)"
		.");\n";

	echo "CREATE TABLE IF NOT EXISTS `entries$i` ("
		."`accountId` int, "
		."`keyPath` varchar(255), "
		."`value` text, "
		."`PubSign` varchar(255), "
		."PRIMARY KEY (`accountId`, `keyPath`)"
		.");\n";

	echo "CREATE TABLE IF NOT EXISTS `messages$i` ("
		."`messageId` int unsigned not null auto_increment, "
		."`accountId` int, "
		."`keyPath` varchar(255), "
		."`value` text, "
		."`PubSign` varchar(255), "
		."PRIMARY KEY (`messageId`)"
		.");\n";

	echo "CREATE TABLE IF NOT EXISTS `emigrants$i` ("
	        ."`accountId` int, "
	        ."`migrationToken` varchar(255), "
	        ."`toUser` varchar(255), "
	        ."`toNode` varchar(255), "
	        ."PRIMARY KEY (`accountId`));\n";

	echo "CREATE TABLE IF NOT EXISTS `immigrants$i` ("
	        ."`accountId` int, "
	        ."`migrationToken` varchar(255), "
	        ."`fromUser` varchar(255), "
	        ."`fromNode` varchar(255), "
	        ."PRIMARY KEY (`accountId`));\n";
	echo "\n";
}
