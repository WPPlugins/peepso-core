<!-- email sent to new users upon registration -->

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta name="viewport" content="width=device-width"/>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<title></title>

	<style type="text/css">
		a {
			color: #00b0ff;
			text-decoration: none;
		}
		a:hover,
		a:focus {
			color: #0092D4;
		}
	</style>
</head>

<body bgcolor="#f7f8f9">
	<div style="background-color: #f7f8f9;">
		<center style="background-color: #f7f8f9;">
			<table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 570px; font-family: Arial, Helvetica, sans-serif; margin: 0; padding-top: 100px; padding-bottom: 100px;">
				<tr>
					<td style="border: 1px solid #e4e6e8; background-color: #fff;">
						<h3 style="color: #00b0ff; margin: 0; padding: 30px; font-size: 26px; line-height: 30px;">
							<a href="{siteurl}" style="text-decoration: none; color: #00b0ff;">{sitename}</a>
						</h3>
					</td>
				</tr>
				<tr>
					<td style="background-color: #fff; border: 1px solid #e4e6e8; border-top: 0; padding: 30px; margin: 0;max-width:505px;">
						<div style="font-size: 14px; line-height: 20px;color: #333;">{email_contents}</div>
					</td>
				</tr>
				<tr>
					<td style="color: #666; font-size: 11px; background-color: #f1f1f1; border: 1px solid #e4e6e8; border-top: 0; padding: 15px 30px; margin: 0;">
						<div style="font-size: 12px;"><?php _e('This email was sent to {currentuserfullname} ({useremail}).', 'peepso-core'); ?></div>
						<br />
						<?php _e('If you do not wish to receive these emails from {sitename}, you can <a href="{unsubscribeurl}">unsubscribe</a> here.', 'peepso-core'); ?>
						<br />
						<?php _e('Copyright (c) {year} {sitename}', 'peepso-core'); ?>
					</td>
				</tr>
			</table>
		</center>
	</div>
</body>
