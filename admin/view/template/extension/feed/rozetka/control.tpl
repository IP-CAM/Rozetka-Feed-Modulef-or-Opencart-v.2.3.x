<!-- Control Panel Component -->
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default modern-panel">
			<div class="panel-heading gradient-header">
				<h3 class="panel-title">
					<i class="fa fa-cogs"></i> Управление фидом
				</h3>
			</div>
			<div class="panel-body">
				<div class="control-buttons">
					<div class="row">
						<div class="col-md-3">
							<button type="button" id="btn-test-generation" class="btn btn-control btn-info">
								<div class="btn-icon">
									<i class="fa fa-flask"></i>
								</div>
								<div class="btn-content">
									<span class="btn-title">Тест генерации</span>
									<small class="btn-description">Проверка на 10 товарах</small>
								</div>
							</button>
						</div>

						<div class="col-md-3">
							<button type="button" id="btn-generate-preview" class="btn btn-control btn-success">
								<div class="btn-icon">
									<i class="fa fa-eye"></i>
								</div>
								<div class="btn-content">
									<span class="btn-title">Предпросмотр</span>
									<small class="btn-description">XML первых товаров</small>
								</div>
							</button>
						</div>

						<div class="col-md-3">
							<button type="button" id="btn-clear-cache" class="btn btn-control btn-warning">
								<div class="btn-icon">
									<i class="fa fa-trash"></i>
								</div>
								<div class="btn-content">
									<span class="btn-title">Очистить кэш</span>
									<small class="btn-description">Удалить временные файлы</small>
								</div>
							</button>
						</div>

						<div class="col-md-3">
							<a href="<?php echo $feed_url; ?>" target="_blank" class="btn btn-control btn-primary">
								<div class="btn-icon">
									<i class="fa fa-external-link"></i>
								</div>
								<div class="btn-content">
									<span class="btn-title">Открыть фид</span>
									<small class="btn-description">Посмотреть XML в браузере</small>
								</div>
							</a>
						</div>
					</div>
				</div>

				<!-- Operation Results -->
				<div id="operation-results" class="operation-results"></div>
			</div>
		</div>
	</div>
</div>