<?php

// Retrieve the requested App from the GET request.
$app = isset( $_GET['app'] ) ? $_GET['app'] : "";

// Switch between the apps and handle the request dependant on each app.
switch( $app ) {
	// An app with no value is assumed to be the individual lookup tool.
	case "":
		// Retrieve additional information from the GET request: Query and Type.
		$query = isset( $_GET['query'] ) ? $_GET['query'] : "";
		$type = isset( $_GET['type'] ) ? $_GET['type'] : "";
		// Redirect back to the individual lookup tool with a reformed URL
		// containing the provided information.
		header("Location: /" . ( $query && $type ? $type . "/" . $query : "" ) );
		break;
	
	// Requested app is for the lookup tool.
	case "lookup":
		// Redirect back to the lookup tool with a reformed URL containing the
		// optional domain component.
		header("Location: /lookup" . ( isset( $_GET['domain'] ) && $_GET['domain'] ? "/" . $_GET['domain'] : "" ));
		break;
	
	// Unknown app, output message in plain text stating so.
	default:
		header("Content-Type: text/plain");
		echo "Unknown app specified.";
		break;
}