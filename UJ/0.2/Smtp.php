<?php
class Smtp {
	public function sendRegistrationToken($email, $registrationToken, $fromNode) {
		file_put_contents('/tmp/mich.log', "sendRegistrationToken('$email', '$registrationToken', '$fromNode')\n", FILE_APPEND);
	}
}
