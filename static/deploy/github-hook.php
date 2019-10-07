<?php

	require('./config.php');

	/**
	 * Automated deploy from GitHub
	 *
	 * https://developer.github.com/webhooks/
	 * Template from ServerPilot (https://serverpilot.io/community/articles/how-to-automatically-deploy-a-git-repo-from-bitbucket.html)
	 * Hash validation from Craig Blanchette (http://isometriks.com/verify-github-webhooks-with-php)
	 */

	function logToFile ($message) {
		file_put_contents(LOG_FILE, date('m/d/Y h:i:s a') . $message . "\n", FILE_APPEND);
	}

	function validateHookSecret () {
		// Get signature
		$hub_signature = $_SERVER['HTTP_X_HUB_SIGNATURE'];

		// Make sure signature is provided
		if (!isset($hub_signature)) {
			logToFile('Error: HTTP header "X-Hub-Signature" is missing');
			die('HTTP header "X-Hub-Signature" is missing.');
		} elseif (!extension_loaded('hash')) {
			logToFile('Error: Missing "hash" extension to check the secret code validity.');
			die('Missing "hash" extension to check the secret code validity.');
		}

		// Split signature into algorithm and hash
		list($algo, $hash) = explode('=', $hub_signature, 2);

		// Get payload
		$payload = file_get_contents('php://input');

		// Calculate hash based on payload and the secret
		$payload_hash = hash_hmac($algo, $payload, GH_DEPLOY_SECRET);

		// Check if hashes are equivalent
		if (!hash_equals($hash, $payload_hash)) {
		    // Kill the script or do something else here.
		    logToFile('Error: Bad Secret');
		    die('Bad secret');
		}
	}

	function deploy () {
		// Do a git checkout, run Hugo, and copy files to public directory
		exec('cd ' . REPOSITORY_DIR . ' && git fetch --all && git reset --hard origin/master');
		exec('cd ' . REPOSITORY_DIR . ' && ' . HUGO_PATH . ' --minify -d ..');

		logToFile("Deployed successfully!");
	}

	validateHookSecret();
	deploy();

?>