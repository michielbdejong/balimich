<?php

for($i=0;$i<256;$i++) {
	echo "CREATE TABLE IF NOT EXISTS `accounts$i` ("
		."`accountId` int unsigned not null auto_increment, "
		."`user` varchar(255), "
		."`node` varchar(255), "
		."`app` varchar(255), "
		."`state` int, "
		."`md5PubPass` varchar(255), "
		."`md5SubPass` varchar(255), "
		."PRIMARY KEY (`accountId`)"
		.");\n";

	echo "CREATE TABLE IF NOT EXISTS `entries$i` ("
		."`accountId` int, "
		."`keyPath` varchar(255), "
		."`value` text, "
		."`PubSign02` varchar(255), "
		."PRIMARY KEY (`accountId`, `keyPath`)"
		.");\n";

	echo "CREATE TABLE IF NOT EXISTS `messages$i` ("
		."`messageId` int unsigned not null auto_increment, "
		."`accountId` int, "
		."`keyPath` varchar(255), "
		."`value` text, "
		."`PubSign02` varchar(255), "
		."PRIMARY KEY (`messageId`)"
		.");\n";

	echo "CREATE TABLE IF NOT EXISTS `creationTokens$i` ("
		."`emailUser` varchar(255), "
		."`emailDomain` varchar(255), "
		."`storageNode` varchar(255), "
		."`app` varchar(255), "
		."`token` varchar(255));\n";
}
