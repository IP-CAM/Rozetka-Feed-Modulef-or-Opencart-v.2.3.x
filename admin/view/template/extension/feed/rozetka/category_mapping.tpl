<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default modern-panel">
			<div class="panel-heading gradient-header">
				<h3 class="panel-title">
					<i class="fa fa-link"></i> Маппинг категорий
				</h3>
			</div>
			<div class="panel-body">
                <!-- File Upload Section -->
                <div id="file-upload-section" class="modern-upload-section">
                    <div class="upload-header">
                        <h4><i class="fa fa-cloud-upload"></i> Импорт категорий из JSON файла</h4>
                        <p class="upload-description">Загрузите JSON файл с категориями Rozetka для импорта в базу данных.</p>
                    </div>

                    <div class="upload-area">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="file-upload-wrapper">
                                    <input type="file" id="categories-file-input" name="categories_file"
                                           accept=".json" class="file-input-hidden">
                                    <div class="file-upload-display" id="file-upload-display">
                                        <div class="upload-icon">
                                            <i class="fa fa-cloud-upload fa-2x"></i>
                                        </div>
                                        <div class="upload-text">
                                            <span class="upload-title">Выберите JSON файл</span>
                                            <span class="upload-subtitle">или перетащите файл сюда</span>
                                        </div>
                                        <div class="upload-info">
                                            <small>Поддерживаются только JSON файлы размером до 10MB</small>
                                        </div>
                                    </div>
                                    <div class="file-selected-info" id="file-selected-info" style="display: none;">
                                        <div class="file-icon">
                                            <i class="fa fa-file-code-o fa-2x"></i>
                                        </div>
                                        <div class="file-details">
                                            <span class="file-name" id="selected-file-name"></span>
                                            <span class="file-size" id="selected-file-size"></span>
                                        </div>
                                        <button type="button" class="btn-remove-file" id="btn-remove-file">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="button" id="btn-upload-categories" class="btn btn-success btn-lg btn-upload" disabled>
                                    <i class="fa fa-upload"></i>
                                    <span>Загрузить категории</span>
                                </button>

                                <div class="upload-actions">
                                    <button type="button" id="btn-clear-categories" class="btn btn-warning btn-sm">
                                        <i class="fa fa-trash"></i> Очистить все категории
                                    </button>
                                    <button type="button" id="btn-download-sample" class="btn btn-info btn-sm">
                                        <i class="fa fa-download"></i> Скачать пример JSON
                                    </button>
                                </div>
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
                                        <input type="text" class="form-control input-sm"
                                               placeholder="Начните вводить название категории..."
                                               id="shop-categories-search"
                                               style="margin-top: 10px;"
                                               data-min-length="2">
                                        <small class="help-block">Минимум 2 символа для поиска (макс. 10 результатов)</small>
                                    </div>
                                </div>
                                <div class="panel-body" style="max-height: 500px; overflow-y: auto;">
                                    <div id="shop-categories-list" class="categories-lazy-container">
                                        <div class="search-prompt">
                                            <i class="fa fa-search fa-2x text-muted"></i>
                                            <p class="text-muted">Введите название категории для поиска</p>
                                        </div>
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
                                        <input type="text" class="form-control input-sm"
                                               placeholder="Начните вводить название категории..."
                                               id="rozetka-categories-search"
                                               style="margin-top: 10px;"
                                               data-min-length="2">
                                        <small class="help-block">Минимум 2 символа для поиска (макс. 10 результатов)</small>
                                    </div>
                                </div>
                                <div class="panel-body" style="max-height: 500px; overflow-y: auto;">
                                    <div id="rozetka-categories-list" class="categories-lazy-container">
                                        <div class="search-prompt">
                                            <i class="fa fa-search fa-2x text-muted"></i>
                                            <p class="text-muted">Введите название категории для поиска</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
					</div>

                    <!-- Quick mapping suggestions -->
                    <div class="row" id="mapping-suggestions" style="display: none;">
                        <div class="col-md-12">
                            <div class="panel panel-info">
                                <div class="panel-heading">
                                    <h4><i class="fa fa-lightbulb-o"></i> Предложения для маппинга</h4>
                                </div>
                                <div class="panel-body">
                                    <div id="suggestions-content">
                                        <!-- Auto-suggestions will be loaded here -->
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
                                    <h4><i class="fa fa-check-circle"></i> Установленные связи (<span id="mappings-count">0</span>)</h4>
                                    <div class="pull-right">
                                        <button type="button" class="btn btn-sm btn-success" id="btn-save-mappings">
                                            <i class="fa fa-save"></i> Сохранить все связи
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning" id="btn-auto-map">
                                            <i class="fa fa-magic"></i> Автоматический маппинг
                                        </button>
                                    </div>
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="mappings-table">
                                            <thead>
                                            <tr>
                                                <th width="40%">Категория магазина</th>
                                                <th width="50%">Категория Rozetka</th>
                                                <th width="10%">Действия</th>
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