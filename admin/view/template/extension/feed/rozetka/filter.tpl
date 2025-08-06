<div class="row">
	<div class="col-md-12">
		<!-- Additional -->
		<div class="panel panel-default modern-panel">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="fa fa-filter"></i> Фильтры товаров
				</h3>
			</div>
			<div class="panel-body">
				<div class="row">
					<!-- Stock Settings -->
					<div class="col-md-4">
						<div class="filter-section">
							<h5 class="filter-title">
								<i class="fa fa-cube"></i> Наличие товаров
							</h5>
							<div class="form-group">
								<label class="control-label"><?php echo $entry_stock; ?></label>

								<div class="toggle-switch">
									<input type="hidden" name="feed_rozetka_stock_status" value="0">
									<input type="checkbox" name="feed_rozetka_stock_status" value="1"
										   id="stock-toggle" <?php echo $feed_rozetka_stock_status ? 'checked' : ''; ?>>
									<label for="stock-toggle" class="toggle-label">
										<span class="toggle-inner"></span>
										<span class="toggle-switch"></span>
									</label>
									<span class="toggle-text">Товары без наличия</span>
								</div>

								<?php if ($help_stock) { ?>
									<div class="help-block">
										<i class="fa fa-info-circle"></i> <?php echo $help_stock; ?>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>

					<!-- Price Range -->
					<div class="col-md-8">
						<div class="filter-section">
							<h5 class="filter-title">
								<i class="fa fa-money"></i> Диапазон цен
							</h5>
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label class="control-label"><?php echo $entry_min_price; ?></label>

										<div class="input-group">
											<input type="text" name="feed_rozetka_min_price"
												   value="<?php echo $feed_rozetka_min_price; ?>"
												   class="form-control modern-input price-input"
												   placeholder="0">
											<span class="input-group-addon"><?php echo $feed_rozetka_currency ?: 'UAH'; ?></span>
										</div>

										<?php if ($help_min_price) { ?>
											<div class="help-block">
												<i class="fa fa-info-circle"></i> <?php echo $help_min_price; ?>
											</div>
										<?php } ?>
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label class="control-label"><?php echo $entry_max_price; ?></label>

										<div class="input-group">
											<input type="text" name="feed_rozetka_max_price"
												   value="<?php echo $feed_rozetka_max_price; ?>"
												   class="form-control modern-input price-input"
												   placeholder="0">
											<span class="input-group-addon"><?php echo $feed_rozetka_currency ?: 'UAH'; ?></span>
										</div>

										<?php if ($error_price_range) { ?>
											<div class="text-danger">
												<i class="fa fa-exclamation-triangle"></i> <?php echo $error_price_range; ?>
											</div>
										<?php } ?>

										<?php if ($help_max_price) { ?>
											<div class="help-block">
												<i class="fa fa-info-circle"></i> <?php echo $help_max_price; ?>
											</div>
										<?php } ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Categories and Manufacturers Filters -->
				<div class="row">
					<!-- Categories Filter -->
					<div class="col-md-6">
						<div class="panel panel-default modern-panel">
							<div class="panel-heading">
								<h3 class="panel-title">
									<i class="fa fa-sitemap"></i> Исключить категории
									<span class="badge" id="categories-counter">0</span>
								</h3>
							</div>
							<div class="panel-body">
								<div class="filter-controls">
									<div class="search-box">
										<input type="text" class="form-control modern-input"
											   placeholder="Поиск категорий..." id="categories-search">
										<i class="fa fa-search search-icon"></i>
									</div>
									<div class="control-buttons">
										<button type="button" class="btn btn-xs btn-default" id="select-all-categories">
											Выбрать все
										</button>
										<button type="button" class="btn btn-xs btn-default" id="deselect-all-categories">
											Снять все
										</button>
									</div>
								</div>

								<div class="filter-tree" id="categories-tree">
									<?php if (!empty($categories)) { ?>
										<?php foreach ($categories as $category) { ?>
											<div class="tree-item" data-name="<?php echo strtolower($category['name']); ?>"
												 data-id="<?php echo $category['category_id']; ?>">
												<label class="tree-label">
													<input type="checkbox" name="feed_rozetka_exclude_categories[]"
														   value="<?php echo $category['category_id']; ?>"
														<?php echo (isset($feed_rozetka_exclude_categories) && in_array($category['category_id'], $feed_rozetka_exclude_categories)) ? 'checked' : ''; ?>>
													<span class="tree-checkbox"></span>
													<span class="tree-text"><?php echo $category['name']; ?></span>
													<span class="tree-count">(ID: <?php echo $category['category_id']; ?>)</span>
												</label>
											</div>
										<?php } ?>
									<?php } else { ?>
										<div class="no-items">
											<i class="fa fa-info-circle"></i> Категории не найдены
										</div>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>

					<!-- Manufacturers Filter -->
					<div class="col-md-6">
						<div class="panel panel-default modern-panel">
							<div class="panel-heading">
								<h3 class="panel-title">
									<i class="fa fa-industry"></i> Исключить производителей
									<span class="badge" id="manufacturers-counter">0</span>
								</h3>
							</div>
							<div class="panel-body">
								<div class="filter-controls">
									<div class="search-box">
										<input type="text" class="form-control modern-input"
											   placeholder="Поиск производителей..." id="manufacturers-search">
										<i class="fa fa-search search-icon"></i>
									</div>
									<div class="control-buttons">
										<button type="button" class="btn btn-xs btn-default" id="select-all-manufacturers">
											Выбрать все
										</button>
										<button type="button" class="btn btn-xs btn-default" id="deselect-all-manufacturers">
											Снять все
										</button>
									</div>
								</div>

								<div class="filter-tree" id="manufacturers-tree">
									<?php if (!empty($manufacturers)) { ?>
										<?php foreach ($manufacturers as $manufacturer) { ?>
											<div class="tree-item" data-name="<?php echo strtolower($manufacturer['name']); ?>"
												 data-id="<?php echo $manufacturer['manufacturer_id']; ?>">
												<label class="tree-label">
													<input type="checkbox" name="feed_rozetka_exclude_manufacturers[]"
														   value="<?php echo $manufacturer['manufacturer_id']; ?>"
														<?php echo (isset($feed_rozetka_exclude_manufacturers) && in_array($manufacturer['manufacturer_id'], $feed_rozetka_exclude_manufacturers)) ? 'checked' : ''; ?>>
													<span class="tree-checkbox"></span>
													<span class="tree-text"><?php echo $manufacturer['name']; ?></span>
													<span class="tree-count">(ID: <?php echo $manufacturer['manufacturer_id']; ?>)</span>
												</label>
											</div>
										<?php } ?>
									<?php } else { ?>
										<div class="no-items">
											<i class="fa fa-info-circle"></i> Производители не найдены
										</div>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>