<!-- Statistics Dashboard Component -->
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default modern-panel">
			<div class="panel-heading gradient-header">
				<h3 class="panel-title">
					<i class="fa fa-dashboard"></i> Панель управления фидом
					<div class="pull-right">
						<button type="button" id="btn-refresh-stats" class="btn btn-xs btn-outline-dark">
							<i class="fa fa-refresh"></i> Обновить
						</button>
					</div>
				</h3>
			</div>
			<div class="panel-body">
				<!-- Statistics Cards -->
				<div class="row statistics-cards" id="statistics-panel">
					<div class="col-lg-3 col-md-6">
						<div class="stats-card card-primary">
							<div class="card-content">
								<div class="stats-icon">
									<i class="fa fa-shopping-cart"></i>
								</div>
								<div class="stats-info">
									<h3 class="stats-number"><?php echo isset($total_products_feed) ? number_format($total_products_feed) : '0'; ?></h3>
									<p class="stats-label">Товаров в фиде</p>
									<div class="stats-trend">
                                                <span class="trend-indicator positive">
                                                    <i class="fa fa-arrow-up"></i>
                                                </span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-3 col-md-6">
						<div class="stats-card card-info">
							<div class="card-content">
								<div class="stats-icon">
									<i class="fa fa-database"></i>
								</div>
								<div class="stats-info">
									<h3 class="stats-number"><?php echo isset($total_products_shop) ? number_format($total_products_shop) : '0'; ?></h3>
									<p class="stats-label">Всего товаров</p>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-3 col-md-6">
						<div class="stats-card card-success">
							<div class="card-content">
								<div class="stats-icon">
									<i class="fa fa-check-circle"></i>
								</div>
								<div class="stats-info">
									<h3 class="stats-number"><?php echo isset($products_in_stock) ? number_format($products_in_stock) : '0'; ?></h3>
									<p class="stats-label">В наличии</p>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-3 col-md-6">
						<div class="stats-card card-warning">
							<div class="card-content">
								<div class="stats-icon">
									<i class="fa fa-image"></i>
								</div>
								<div class="stats-info">
									<h3 class="stats-number"><?php echo isset($products_with_images) ? number_format($products_with_images) : '0'; ?></h3>
									<p class="stats-label">С изображениями</p>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Generation Status -->
				<?php if (isset($last_generation) && $last_generation) { ?>
					<div class="generation-status-card">
						<div class="row">
							<div class="col-md-8">
								<h4 class="status-title">
									<i class="fa fa-clock-o"></i> Последняя генерация
								</h4>
								<div class="status-info">
									<span class="status-date"><?php echo date('d.m.Y H:i:s', strtotime($last_generation['date_generated'])); ?></span>
									<div class="status-details">
                                                <span class="detail-item">
                                                    <i class="fa fa-shopping-cart"></i>
                                                    Товаров: <strong><?php echo number_format($last_generation['products_count']); ?></strong>
                                                </span>
										<span class="detail-item">
                                                    <i class="fa fa-clock-o"></i>
                                                    Время: <strong><?php echo $last_generation['generation_time']; ?>с</strong>
                                                </span>
										<span class="detail-item">
                                                    <i class="fa fa-file-o"></i>
                                                    Размер: <strong><?php echo $last_generation['file_size']; ?></strong>
                                                </span>
									</div>
								</div>
							</div>
							<div class="col-md-4">
								<div class="status-badge-container">
									<?php if ($last_generation['status'] == 'success') { ?>
										<span class="status-badge status-success">
                                                    <i class="fa fa-check"></i> Успешно
                                                </span>
									<?php } else { ?>
										<span class="status-badge status-error">
                                                    <i class="fa fa-exclamation-triangle"></i> Ошибка
                                                </span>
									<?php } ?>
								</div>
								<?php if ($last_generation['status'] == 'error' && !empty($last_generation['error_message'])) { ?>
									<div class="error-message">
										<small><?php echo $last_generation['error_message']; ?></small>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>