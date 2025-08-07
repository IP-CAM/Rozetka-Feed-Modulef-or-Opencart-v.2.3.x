<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default modern-panel">
			<div class="panel-heading gradient-header">
				<h3 class="panel-title">
					<i class="fa fa-link"></i> Маппинг категорий
					<div class="pull-right">
						<button type="button" id="btn-update-rozetka-categories" class="btn btn-xs btn-outline-dark">
							<i class="fa fa-refresh"></i> Обновить категории Rozetka
						</button>
					</div>
				</h3>
			</div>
			<div class="panel-body">
                <!-- File Upload Section -->
                <div id="file-upload-section" class="alert alert-info">
                    <h4><i class="fa fa-upload"></i> Импорт категорий из JSON файла</h4>
                    <p>Загрузите JSON файл с категориями Rozetka для импорта в базу данных.</p>


                    <form id="categories-upload-form" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <input type="file" id="categories-file-input" name="categories_file"
                                           accept=".json" class="form-control modern-input">
                                    <small class="help-block">
                                        <i class="fa fa-info-circle"></i>
                                        Поддерживаются только JSON файлы размером до 10MB
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="button" id="btn-upload-categories" class="btn btn-success btn-block" disabled>
                                    <i class="fa fa-upload"></i> Загрузить категории
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="btn-group btn-group-sm">
                                <button type="button" id="btn-clear-categories" class="btn btn-warning">
                                    <i class="fa fa-trash"></i> Очистить все категории
                                </button>
                                <button type="button" id="btn-download-sample" class="btn btn-info">
                                    <i class="fa fa-download"></i> Скачать пример JSON
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress indicator -->
                <div id="import-progress" class="alert alert-info" style="display: none;">
                    <div class="progress-info">
                        <i class="fa fa-spinner fa-spin"></i>
                        <span id="progress-text">Импортируем категории...</span>
                    </div>
                    <div class="progress" style="margin-top: 10px;">
                        <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%">
                            <span id="progress-percent">0%</span>
                        </div>
                    </div>
                </div>

                <!-- Upload Results -->
                <div id="upload-results" style="display: none;"></div>

				<!-- Mapping interface -->
				<div id="mapping-interface">
					<div class="row">
						<!-- Shop categories -->
						<div class="col-md-6">
							<div class="panel panel-default">
								<div class="panel-heading">
									<h4><i class="fa fa-sitemap"></i> Категории магазина</h4>
									<div class="panel-controls">
										<input type="text" class="form-control input-sm" placeholder="Поиск категорий..." id="shop-categories-search" style="margin-top: 10px;">
									</div>
								</div>
								<div class="panel-body" style="max-height: 500px; overflow-y: auto;">
									<div id="shop-categories-list">
										<!-- Will be populated by AJAX -->
									</div>
								</div>
							</div>
						</div>

						<!-- Rozetka categories -->
						<div class="col-md-6">
							<div class="panel panel-default">
								<div class="panel-heading">
									<h4><i class="fa fa-external-link"></i> Категории Rozetka</h4>
									<div class="panel-controls">
										<input type="text" class="form-control input-sm" placeholder="Поиск категорий..." id="rozetka-categories-search" style="margin-top: 10px;">
									</div>
								</div>
								<div class="panel-body" style="max-height: 500px; overflow-y: auto;">
									<div id="rozetka-categories-list">
										<!-- Will be populated by AJAX -->
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Mapping results -->
					<div class="row">
						<div class="col-md-12">
							<div class="panel panel-default">
								<div class="panel-heading">
									<h4><i class="fa fa-check-circle"></i> Установленные связи</h4>
									<div class="pull-right">
										<button type="button" class="btn btn-sm btn-success" id="btn-save-mappings">
											<i class="fa fa-save"></i> Сохранить все связи
										</button>
									</div>
								</div>
								<div class="panel-body">
									<div class="table-responsive">
										<table class="table table-bordered" id="mappings-table">
											<thead>
											<tr>
												<th>Категория магазина</th>
												<th>Категория Rozetka</th>
												<th>Действия</th>
											</tr>
											</thead>
											<tbody id="mappings-tbody">
											<!-- Will be populated dynamically -->
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>