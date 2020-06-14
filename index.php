<?php

// Prepare a fallback list of DNS record types.
$types = ['a', 'aaaa', 'cname', 'ip', 'mx', 'ns', 'soa', 'srv', 'txt'];
// Start forming the URL to the API for this request.
$api_url = "https://api.dns.scot/lookup/";

// Fetch a list of DNS record types from the API.
$types_from_api = json_decode( file_get_contents( $api_url . "types" ) );
// Check the API response contains an actual list of supported types.
if( $types_from_api instanceof stdclass && property_exists( $types_from_api, "supported_types" ) ) {
	// Replace our fallback list with the one we received from the API.
	$types = $types_from_api->supported_types;
}

/**
 *		Perform a DNS record lookup request to the API.
 *		Outputs a table suitable for and containing the results from the API.
 *		@param String $query The requested query (e.g. hostname or IP) to lookup.
 *		@param String $type The requested DNS record type to lookup.
 */
function lookup( $query, $type ) {
	// Access the list of record types and the current API URL from outside
	// the function by marking them as global variables.
	global $types, $api_url;
	
	// Check the record type requested is a supported record type.
	if( in_array( strtoupper( $type ), $types ) ) {
		// Form the rest of the API URL using information
		// from this request.
		$api_url = $api_url . $type . "/" . $query;
		// Decode the JSON response received from the API.
		// Effectively turning the JSON into an object.
		$api = json_decode( file_get_contents( $api_url ) );
		
		// Check the decoded API response is indeed an opject (stdclass)
		// and that a property called "result" exists.
		if( $api instanceof stdclass && property_exists( $api, "result" ) ):
			// Save the result of the lookup to a variable.
			$result = $api->result;
			// Check that the result is an array and it contains
			// at least one result.
			if( is_array( $result ) && count( $result ) >= 1 ):
				// Output a responsive HTML table suitable for and containing
				// the results from the API response results.
				echo '<div class="dcg-responsive">';
				echo '<table class="dcg-table dcg-striped dcg-padding">';
				echo '	<tr>';
				echo '		<td><b>HOST</b></td>';
				echo '		<td><b>CLASS</b></td>';
				echo '		<td><b>TTL</b></td>';
				echo '		<td><b>TYPE</b></td>';
				echo '		<td><b>';
					// Switch out the last column containing the record values
					// depending on which record type was looked up.
					switch( strtolower( $type ) ) {
						case 'a':
							echo 'IPv4';
							break;
						case 'aaaa':
							echo 'IPv6';
							break;
						case 'cname':
						case 'ns':
							echo 'TARGET';
							break;
						case 'mx':
							echo '[PRIORITY] TARGET';
							break;
						case 'soa':
							echo 'DATA';
							break;
						case 'txt':
							echo 'TEXT';
							break;
						default:
							echo 'RAW DATA';
							break;
					}
				echo '</b></td>';
				echo '	</tr>';
				// Loop through the results and create a row for each result
				// in the HTML table.
					foreach( $result as $r ):
					echo '<tr>';
					echo '	<td>' . $r->host . '</td>';
					echo '	<td>' . $r->class . '</td>';
					echo '	<td>' . $r->ttl . '</td>';
					echo '	<td>' . $r->type . '</td>';
					// Differentiate between each record type and display the right
					// information and in the right format in the table.
					switch( strtolower( $type ) ):
						case 'a':
							echo '	<td>';
							echo $r->ip;
							// A records contain IPv4 addresses, perform a quick lookup
							// for a corresponding hostname and decode the result.
							$ip_lookup = json_decode( file_get_contents("https://api.dns.scot/lookup/ip/" . $r->ip) );
							// Check the result is an object (stdclass) and that the
							// "hostname" property exists, and output the hostname below
							// the IPv4 address.
							if( $ip_lookup instanceof stdclass && property_exists( $ip_lookup, "hostname" ) ) 
								echo '<br><span class="dcg-small">' . $ip_lookup->hostname . '</span>';
							echo '</td>';
							break;
						case 'aaaa':
							echo '	<td>';
							echo $r->ipv6;
							// AAAA records contain IPv6 addresses, perform a quick lookup
							// for a corresponding hostname and decode the result.
							$ip_lookup = json_decode( file_get_contents("https://api.dns.scot/lookup/ip/" . $r->ipv6) );
							// Check the result is an object (stdclass) and that the
							// "hostname" property exists, and output the hostname below
							// the IPv6 address.
							if( $ip_lookup instanceof stdclass && property_exists( $ip_lookup, "hostname" ) ) 
								echo '<br><span class="dcg-small">' . $ip_lookup->hostname . '</span>';
							echo '</td>';
							break;
						case 'cname':
						case 'ns':
							echo '	<td>' . $r->target . '</td>';
							break;
						case 'mx':
							echo '	<td>[' . $r->pri . '] ' . $r->target . '</td>';
							break;
						case "soa":
							// SOA records contain a lot more information. Output a table
							// to better display this information.
							echo "<td><table class=\"dcg-table\"><tr><td colspan=\"2\"><b>NS</b></td><td colspan=\"3\"><b>Email</b></td></tr><tr><td colspan=\"2\">" . $r->mname . "</td><td colspan=\"3\">" . substr_replace( $r->rname, "@", strpos( $r->rname, "." ), 1 ) . "</td></tr><tr><td><b>Serial</b></td><td><b>Refresh</b></td><td><b>Retry</b></td><td><b>Expire</b></td><td><b>Min TTL</b></td></tr><tr><td>" . $r->serial . "</td><td>" . $r->refresh . "</td><td>" . $r->retry . "</td><td>" . $r->expire . "</td><td>" . $r->{'minimum-ttl'} . "</td></tr></table></td>";
							break;
						case 'txt':
							echo '	<td>' . $r->txt . '</td>';
							break;
						default:
							echo '	<td><pre>' . print_r( $r, true ) . '</pre></td>';
							break;
					endswitch;
					echo '</tr>';
				endforeach;
				echo '</table></div>';
			endif;
		
		// Not a normal record lookup, perhaps an IP hostname lookup?
		// Check the decoded API response is indeed an object (stdclass) and
		// that the properties "ip" and "hostname" exists.
		elseif( $api instanceof stdclass && property_exists( $api, "ip" ) && property_exists( $api, "hostname" ) ):
			// Output a responsive HTML table containing the IP and corresponding
			// hostname received from the API.
			echo '<div class="dcg-responsive">';
			echo '	<table class="dcg-table dcg-striped dcg-padding">';
			echo '		<tr><td><b>IP</b></td><td><b>HOSTNAME</b></td></tr>';
			echo '		<tr><td>' . $api->ip . '</td><td>' . $api->hostname . '</td></tr>';
			echo '	</table>';
			echo '</div>';
		
		// Not an IP lookup, perhaps an error was returned?
		// Check the decoded API response is indeed an object (stdclass) and
		// that the properties "type", "code", and "message" exist, and that
		// the type is equals to "error".
		elseif( $api instanceof stdclass && property_exists( $api, "type" ) && $api->type == "error" && property_exists( $api, "code" ) && property_exists( $api, "message" ) ):
			// Output the error code and message returned from the API.
			echo '<div class="dcg-text-red dcg-padding-large"><b>' . $api->code . '</b><br>' . $api->message . '</div>';
		
		// Anything else would mean an unknown error occurred. Output a message
		// indicating this.
		else:
			echo '<div class="dcg-text-red dcg-padding-large">An unknown error occurred!</div>';
		endif;
	
	// The requested record type is unsupported, output a message stating so.
	} else {
		echo '<div class="dcg-text-red dcg-padding-large">Sorry, the <b>' . $type . '</b> record type is unsupported.</div>';
	}
}

// Lastly, output the full HTML of the page containing any dynamic information.
?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<title>DNS Lookup Tool</title>
		
		<link rel="stylesheet" type="text/css" href="https://www.davecomputergeek.scot/dcgcss/dcg.css">
	</head>
	
	<body class="dcg-lighter-light-grey dcg-sans-serif">
		<?php $query = isset( $_GET['query'] ) ? $_GET['query'] : ""; ?>
		<?php $type = isset( $_GET['type'] ) ? $_GET['type'] : ""; ?>
		<header class="dcg-container dcg-margin-top-xlarge dcg-padding-top-xlarge dcg-margin-bottom-xlarge dcg-padding-bottom-xlarge">
			<div class="dcg-xxlarge dcg-link-deep-red dcg-center"><b><a href="/" style="text-decoration: none;">DNS LOOKUP TOOL</a></b></div>
			<div class="">by Dave Computer Geek</div>
		</header>
		
		<section class="dcg-container dcg-margin-top-xlarge dcg-margin-bottom-xlarge dcg-padding-top-xlarge dcg-padding-bottom-xlarge">
			<form method="GET" action="<?php echo $_SERVER['HTTP_HOST'] == "dns.scot" || $_SERVER['HTTP_HOST'] == "www.dns.scot" ? "/router.php" : "index.php" ?>">
				<div class="dcg-row">
					<input type="text" name="query" class="dcg-half dcg-large dcg-white dcg-border dcg-border-none dcg-border-top dcg-border-bottom dcg-border-grey dcg-padding" autocorrect="off" autocapitalize="none"<?php echo $query ? ' value="' . $query . '"' : ''; ?>>
					<select name="type" class="dcg-quarter dcg-large dcg-padding">
						<?php /* Loop through each supported record type. */ ?>
						<?php /* Output each record type as an HTML select option. */ ?>
						<?php foreach( $types as $t ): ?>
						<option value="<?php echo strtolower( $t ); ?>"<?php echo strtoupper( $type ) == $t ? ' selected' : ''; ?>><?php echo strtoupper( $t ); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="dcg-quarter dcg-large dcg-deep-red dcg-text-white dcg-padding">GO</button>
				</div>
				<input type="hidden" name="app" value="">
			</form>
		</section>
		
		<?php /* Check if a query and record type was requested. */ ?>
		<?php if( $query && $type ): ?>
		<section class="dcg-container dcg-white dcg-border dcg-border-topbar dcg-border-bottombar dcg-border-darker-light-grey">
			<?php /* Perform a lookup for the query and record type requested. */ ?>
			<?php lookup( $query, $type ); ?>
		</section>
		<?php endif; ?>
		
		<footer class="dcg-padding-xlarge dcg-small dcg-center dcg-link-grey dcg-text-grey">
			<a href="https://github.com/DaveComputerGeek/DNS-Lookup-API" target="_blank">View the Open Source DNS Lookup API on GitHub</a>.
			
			<?php /* Verify an API URL is available from above and output */ ?>
			<?php /* a link in the footer directly to the API's raw result. */ ?>
			<?php if( isset( $api_url ) && $api_url ): ?>
				<br><a href="<?php echo $api_url; ?>" target="_blank">View the API for this page</a>.
			<?php endif; ?>
			
			<p class="dcg-center dcg-small">Designed by <a href="https://dcg.scot" target="_blank">Dave Computer Geek</a> using <a href="https://dcg.scot/dcgcss" target="_blank">DCG.CSS</a>.</p>
		</footer>
	</body>
</html>