<?php date_default_timezone_set('Europe/London'); ?><!DOCTYPE html>
<html lang="en">

	<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">

		<meta name="viewport" content="width=device-width, initial-scale=1">

		<title>DNS Lookup Tool :: dns.scot/lookup</title>

		<link rel="stylesheet" type="text/css" href="https://www.davecomputergeek.scot/dcgcss/dcg.css">
	</head>

	<body class="dcg-sans-serif">
	    <div class="dcg-row">
			<div class="dcg-s1">&nbsp;</div>
        	<div class="dcg-s10 dcg-left-align">
        		<h1 class="dcg-xxxlarge dcg-text-deep-red dcg-center">DNS Lookup Tool</h1>
        		<p class="dcg-center"><a href="https://dns.scot/lookup" class="dcg-text-black">dns.scot/lookup</a></p>
        		<hr>

				<?php $placeholder_domain = substr( $_SERVER['HTTP_HOST'], 0, 4 ) == "www." ? substr( $_SERVER['HTTP_HOST'], 4 ) : $_SERVER['HTTP_HOST']; ?>
        		<?php $domain = isset( $_GET['domain'] ) ? $_GET['domain'] : ""; ?>

        		<?php if( $domain ): ?>
        		    <?php $update = isset( $_GET['update'] ) ? $_GET['update'] : ""; ?>
        			<?php $api = file_get_contents("https://api.dns.scot/lookup/domain/" . $domain . ( $update == "update" ? "/update" : "" )); ?>
        			<?php $api = json_decode( $api ); ?>
        			<?php if( $update == "update" && property_exists( $api, "domain" ) ) { echo "<h2>Updating...</h2><META HTTP-EQUIV=\"Refresh\" CONTENT=\"1; URL=/lookup/" . $api->domain . "\">"; exit; } ?>
        			<?php //echo "<pre>" . print_r( $api, true ) . "</pre>"; ?>
        			<?php if( property_exists( $api, "data" ) ): ?>
        			    <form method="get" action="/router">
        			        <input type="text" name="domain" class="dcg-full dcg-large dcg-border dcg-border-none dcg-border-bottom dcg-no-bg" autocorrect="off" autocapitalize="none" value="<?php echo $domain; ?>" placeholder="<?php echo $placeholder_domain; ?>">
        			        <input type="hidden" name="app" value="lookup">
        		        </form>
						<p>&nbsp;</p>
        			    <?php $api->data = json_decode( $api->data ); ?>
        				<?php //echo "<pre>" . print_r( $api->data, true ) . "</pre>"; ?>
								<div class="dcg-responsive">
									<table class="dcg-table dcg-striped dcg-padding w3-bordered">
        			       	<tr><td><b>Host</b></td><td><b>Type</b></td><td><b>TTL</b></td><td><b>Data</b></td></tr>
        			    <?php foreach( $api->data as $record ): ?>
        			        	<tr>
        			         		<td><?php echo $record->host; ?></td>
        			            <td><?php echo $record->type; ?></td>
        			            <td><?php echo $record->ttl; ?></td>
        			            <?php

        			            switch( $record->type ):
        			                case "SOA":
        			                    echo "<td><table class=\"dcg-table\"><tr><td colspan=\"2\"><b>NS</b></td><td colspan=\"3\"><b>Email</b></td></tr><tr><td colspan=\"2\">" . $record->mname . "</td><td colspan=\"3\">" . substr_replace( $record->rname, "@", strpos( $record->rname, "." ), 1 ) . "</td></tr><tr><td><b>Serial</b></td><td><b>Refresh</b></td><td><b>Retry</b></td><td><b>Expire</b></td><td><b>Min TTL</b></td></tr><tr><td>" . $record->serial . "</td><td>" . $record->refresh . "</td><td>" . $record->retry . "</td><td>" . $record->expire . "</td><td>" . $record->{'minimum-ttl'} . "</td></tr></table></td>";
        			                    //echo "<td><pre>" . print_r( $record, true ) . "</pre></td>";
        			                    break;
        			                case "A":
            			                echo "<td>";
            			                echo $record->ip;
            			                $hostname = json_decode( file_get_contents("https://api.dns.scot/lookup/ip/" . $record->ip) );
            			                echo property_exists( $hostname, 'hostname' ) ? "<br><sup>" . $hostname->hostname . "</sup>" : "";
            			                echo "</td>";
            			                break;
            			            case "AAAA":
            			                echo "<td>";
            			                echo $record->ipv6;
            			                $hostname = json_decode( file_get_contents("https://api.dns.scot/lookup/ip/" . $record->ipv6) );
            			                echo property_exists( $hostname, 'hostname' ) ? "<br><sup>" . $hostname->hostname . "</sup>" : "";
            			                echo "</td>";
            			                break;
            			            case "CNAME":
            			                echo "<td>" . $record->target . "</td>";
            			                break;
            			            case "MX":
            			                echo "<td>[" . $record->pri . "] " . $record->target . "</td>";
            			                break;
            			            case "TXT":
            			                echo "<td>" . $record->txt . "</td>";
            			                break;
            			            case "NS":
            			                echo "<td>" . $record->target . "</td>";
            			                break;
            			            default:
            			                echo "<td>[Coming Soon]</td>";
            			                break;
        			            endswitch;
        			            ?>
        			        </tr>
        			    		<?php endforeach; ?>
        			    		</table>
        			    </div>
        			    <?php $last_updated = new DateTime( $api->last_updated ); ?>
        			    <p>Information accurate as of <?php echo $last_updated->format('l jS F Y \a\t g:i a'); ?> UK time.<?php echo property_exists($api, "minutes_till_manual_update") ? " [Update in " . $api->minutes_till_manual_update . " minutes]" : " [<a href=\"/lookup/" . $domain . "/update\">Update Now</a>]"; ?></p>
        			<?php elseif( property_exists( $api, "type" ) && $api->type == "error" && property_exists( $api, "code" ) && property_exists( $api, "message" ) ): ?>
        		        <div class="w3-panel w3-pale-red w3-round-xxlarge w3-center">
        		            <p><?php echo $api->message; ?></p>
                        </div>
        		        <form method="get" action="/router" style="max-width: 600px; margin-left: auto; margin-right: auto;">
        			        <input type="text" name="domain" class="dcg-full dcg-large dcg-border dcg-border-none dcg-border-bottom dcg-no-bg" autocorrect="off" autocapitalize="none" value="<?php echo $domain; ?>" placeholder="<?php echo $placeholder_domain; ?>">
        			        <input type="hidden" name="app" value="lookup">
        		        </form>
        			<?php endif; ?>
        		<?php else: ?>
        		    <form method="get" action="./router" style="max-width: 600px; margin-left: auto; margin-right: auto;">
        			    <input type="text" name="domain" class="dcg-full dcg-large dcg-border dcg-border-none dcg-border-bottom dcg-no-bg" autocorrect="off" autocapitalize="none" value="<?php echo $domain; ?>" placeholder="<?php echo $placeholder_domain; ?>">
        			    <input type="hidden" name="app" value="lookup">
        		    </form>
        		<?php endif; ?>
        	</div>
        </div>
     <p class="dcg-center dcg-link-black dcg-small">Designed by <a href="https://dcg.scot" target="_blank">Dave Computer Geek</a> using <a href="https://dcg.scot/dcgcss" target="_blank">DCG.CSS</a>.</p>
	</body>

</html>