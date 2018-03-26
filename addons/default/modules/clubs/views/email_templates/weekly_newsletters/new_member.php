						<!-- new member -->
						<tr>
							<td style="padding-top: 0px !important;padding-bottom: 0px !important;">
								<table style="width: 640px;">
									<tr>
										<td style="width: 140px;">
											<img src="<?=$img_source?>" style="border: none; width: 140px; height: auto;" />                          
										</td>
										<td style="width: 15px;">&nbsp;</td>
										<td style="vertical-align: top; text-align: justify;" valign="top">
											<p style="text-align: justify; font-family: verdana,geneva; font-size: 13px; line-height: 15px; font-weight: normal; margin-top: 0px; padding-top: 0px;">
												<a href='<?=base_url()?>profile/<?=$username?>'><?=$display_name?></a>
												<br />
												<br />
												<span style="font-size: 12px;">
													<span style="font-weight: bold;">Location:</span> <?=ucfirst($location_city)?>
													<?=$work?>
													<?=$education?>
													<br />
													<span style="font-weight: bold;">Joined:</span> <?=$joined?>
												</span>
											</p>
										</td>
									</tr> 							
								</table>    
							</td>
						</tr>