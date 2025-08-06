<div class="row">
	<div class="col-md-6">
		<!-- General Settings Component -->
		<div class="panel panel-default modern-panel">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="fa fa-cog"></i> Основные настройки
				</h3>
			</div>
			<div class="panel-body">
				<div class="form-group">
					<label class="control-label required">
						<i class="fa fa-power-off"></i> <?php echo $entry_status; ?>
					</label>
					<div class="toggle-switch">
						<input type="hidden" name="feed_rozetka_status" value="0">
						<input type="checkbox" name="feed_rozetka_status" value="1" id="status-toggle"
							<?php echo $feed_rozetka_status ? 'checked' : ''; ?>>
						<label for="status-toggle" class="toggle-label">
							<span class="toggle-inner"></span>
							<span class="toggle-switch"></span>
						</label>
						<span class="toggle-text">
                                        <?php echo $feed_rozetka_status ? $text_enabled : $text_disabled; ?>
                                    </span>
					</div>
				</div>

				<div class="form-group">
					<label class="control-label">
						<i class="fa fa-store"></i> <?php echo $entry_shop_name; ?>
					</label>
					<input type="text" name="feed_rozetka_shop_name"
						   value="<?php echo $feed_rozetka_shop_name; ?>"
						   class="form-control modern-input"
						   placeholder="<?php echo $placeholder_shop_name ?? 'Введите название магазина'; ?>">
					<?php if ($help_shop_name) { ?>
						<div class="help-block">
							<i class="fa fa-info-circle"></i> <?php echo $help_shop_name; ?>
						</div>
					<?php } ?>
				</div>

				<div class="form-group">
					<label class="control-label">
						<i class="fa fa-building"></i> <?php echo $entry_company; ?>
					</label>
					<input type="text" name="feed_rozetka_company"
						   value="<?php echo $feed_rozetka_company; ?>"
						   class="form-control modern-input"
						   placeholder="<?php echo $placeholder_company ?? 'Введите название компании'; ?>">
					<?php if ($help_company) { ?>
						<div class="help-block">
							<i class="fa fa-info-circle"></i> <?php echo $help_company; ?>
						</div>
					<?php } ?>
				</div>

				<div class="form-group">
					<label class="control-label">
						<i class="fa fa-money"></i> <?php echo $entry_currency; ?>
					</label>
					<select name="feed_rozetka_currency" class="form-control modern-select">
						<?php foreach ($currencies as $currency) { ?>
							<option value="<?php echo $currency['code']; ?>"
								<?php echo ($currency['code'] == $feed_rozetka_currency) ? 'selected' : ''; ?>>
								<?php echo $currency['title']; ?>
							</option>
						<?php } ?>
					</select>
					<?php if ($help_currency) { ?>
						<div class="help-block">
							<i class="fa fa-info-circle"></i> <?php echo $help_currency; ?>
						</div>
					<?php } ?>
				</div>

				<!-- Feed URL Display -->
				<div class="form-group">
					<label class="control-label">
						<i class="fa fa-link"></i> URL фида
					</label>
					<div class="input-group">
						<input type="text" value="<?php echo $feed_url; ?>"
							   class="form-control modern-input" readonly>
						<span class="input-group-btn">
                                        <button type="button" class="btn btn-default" onclick="copyToClipboard('<?php echo $feed_url; ?>')">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </span>
					</div>
					<?php if ($help_feed_url) { ?>
						<div class="help-block">
							<i class="fa fa-info-circle"></i> <?php echo $help_feed_url; ?>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>

	<div class="col-md-6">
		<!-- Images Settings Component -->
		<div class="panel panel-default modern-panel">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="fa fa-image"></i> Настройки изображений
				</h3>
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label class="control-label">
								<i class="fa fa-arrows-h"></i> <?php echo $entry_image_width; ?>
							</label>
							<div class="input-group">
								<input type="text" name="feed_rozetka_image_width"
									   value="<?php echo $feed_rozetka_image_width; ?>"
									   class="form-control modern-input" placeholder="800">
								<span class="input-group-addon">px</span>
							</div>
							<?php if ($error_image_width) { ?>
								<div class="text-danger">
									<i class="fa fa-exclamation-triangle"></i> <?php echo $error_image_width; ?>
								</div>
							<?php } ?>
						</div>
					</div>

					<div class="col-md-6">
						<div class="form-group">
							<label class="control-label">
								<i class="fa fa-arrows-v"></i> <?php echo $entry_image_height; ?>
							</label>
							<div class="input-group">
								<input type="text" name="feed_rozetka_image_height"
									   value="<?php echo $feed_rozetka_image_height; ?>"
									   class="form-control modern-input" placeholder="800">
								<span class="input-group-addon">px</span>
							</div>
							<?php if ($error_image_height) { ?>
								<div class="text-danger">
									<i class="fa fa-exclamation-triangle"></i> <?php echo $error_image_height; ?>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>

				<div class="form-group">
					<label class="control-label">
						<i class="fa fa-compress"></i> <?php echo $entry_image_quality; ?>
					</label>
					<div class="quality-slider">
						<input type="range" name="feed_rozetka_image_quality"
							   value="<?php echo $feed_rozetka_image_quality ?: 90; ?>"
							   min="50" max="100" step="10"
							   class="form-control-range" id="quality-slider">
						<div class="slider-labels">
							<span>50%</span>
							<span>60%</span>
							<span>70%</span>
							<span>80%</span>
							<span>90%</span>
							<span>100%</span>
						</div>
						<div class="slider-value">
							<span id="quality-value"><?php echo $feed_rozetka_image_quality ?: 90; ?>%</span>
						</div>
					</div>
					<?php if ($help_image_quality) { ?>
						<div class="help-block">
							<i class="fa fa-info-circle"></i> <?php echo $help_image_quality; ?>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>

		<!-- Content Settings -->
		<div class="panel panel-default modern-panel">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="fa fa-file-text"></i> Настройки контента
				</h3>
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-md-4">
						<div class="form-group">
							<label class="control-label">
								<i class="fa fa-text-width"></i> <?php echo $entry_description; ?>
							</label>
							<div class="input-group">
								<input type="text" name="feed_rozetka_description_length"
									   value="<?php echo $feed_rozetka_description_length; ?>"
									   class="form-control modern-input" placeholder="3000">
								<span class="input-group-addon">симв.</span>
							</div>
							<?php if ($help_description) { ?>
								<div class="help-block">
									<i class="fa fa-info-circle"></i> <?php echo $help_description; ?>
								</div>
							<?php } ?>
						</div>
					</div>

					<div class="col-md-4">
						<div class="form-group">
							<label class="control-label">
								<i class="fa fa-code"></i> <?php echo $entry_description_tags; ?>
							</label>
							<div class="toggle-switch small">
								<input type="hidden" name="feed_rozetka_description_strip_tags" value="0">
								<input type="checkbox" name="feed_rozetka_description_strip_tags" value="1"
									   id="strip-tags-toggle" <?php echo $feed_rozetka_description_strip_tags ? 'checked' : ''; ?>>
								<label for="strip-tags-toggle" class="toggle-label">
									<span class="toggle-inner"></span>
									<span class="toggle-switch"></span>
								</label>
							</div>
						</div>
					</div>

					<div class="col-md-4">
						<div class="form-group">
							<label class="control-label">
								<i class="fa fa-list-ul"></i> <?php echo $entry_options; ?>
							</label>
							<div class="toggle-switch small">
								<input type="hidden" name="feed_rozetka_include_options" value="0">
								<input type="checkbox" name="feed_rozetka_include_options" value="1"
									   id="options-toggle" <?php echo $feed_rozetka_include_options ? 'checked' : ''; ?>>
								<label for="options-toggle" class="toggle-label">
									<span class="toggle-inner"></span>
									<span class="toggle-switch"></span>
								</label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>