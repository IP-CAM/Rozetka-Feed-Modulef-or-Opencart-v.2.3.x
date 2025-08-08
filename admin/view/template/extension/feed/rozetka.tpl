<?php echo $header; ?><?php echo $column_left; ?>
    <div id="content">
        <div class="page-header">
            <div class="container-fluid">
                <div class="pull-right">
                    <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default btn-lg">
                        <i class="fa fa-reply"></i> <?php echo $button_cancel; ?>
                    </a>
                </div>
                <h1><i class="fa fa-rss-square text-primary"></i> <?php echo $heading_title; ?></h1>
                <ul class="breadcrumb">
					<?php foreach ($breadcrumbs as $breadcrumb) { ?>
                        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
					<?php } ?>
                </ul>
            </div>
        </div>

        <div class="container-fluid">
			<?php if ($error_warning) { ?>
                <div class="alert alert-danger alert-dismissible fade in">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
                </div>
			<?php } ?>
            <ul class="nav nav-tabs modern-tabs" role="tablist">
                <li class="active">
                    <a href="#tab-dashboard" data-toggle="tab">
                        <i class="fa fa-dashboard"></i> Панель управления
                    </a>
                </li>
                <li>
                    <a href="#tab-settings" data-toggle="tab">
                        <i class="fa fa-cog"></i> Настройки
                    </a>
                </li>
                <li>
                    <a href="#tab-filters" data-toggle="tab">
                        <i class="fa fa-filter"></i> Фильтры товаров
                    </a>
                </li>

                <li>
                    <a href="#tab-mapping" data-toggle="tab">
                        <i class="fa fa-link"></i> Маппинг категорий
                    </a>
                </li>

                <li>
                    <a href="#tab-history" data-toggle="tab">
                        <i class="fa fa-history"></i> История генераций
                    </a>
                </li>
            </ul>

            <div class="tab-content modern-tab-content">
                <div class="tab-pane active" id="tab-dashboard">
                    <?php include("rozetka/statistics.tpl"); ?>

                    <?php include("rozetka/control.tpl"); ?>
                </div>

                <div class="tab-pane" id="tab-settings">
                    <div class="tab-header-controls">
                        <button type="button" class="btn btn-primary btn-save-tab" data-tab="settings">
                            <i class="fa fa-save"></i> Сохранить настройки
                        </button>
                    </div>
					<?php include("rozetka/base_setting.tpl"); ?>
                </div>

                <div class="tab-pane" id="tab-filters">
                    <div class="tab-header-controls">
                        <button type="button" class="btn btn-primary btn-save-tab" data-tab="filters">
                            <i class="fa fa-save"></i> Сохранить фильтры
                        </button>
                    </div>
					<?php include("rozetka/filter.tpl"); ?>
                </div>

                <div class="tab-pane" id="tab-mapping">
                    <div class="tab-header-controls">
                        <button type="button" class="btn btn-primary btn-save-tab" data-tab="mapping">
                            <i class="fa fa-save"></i> Сохранить связи
                        </button>
                    </div>
					<?php include("rozetka/category_mapping.tpl"); ?>
                </div>

                <div class="tab-pane" id="tab-history">
                    <?php include("rozetka/history.tpl"); ?>
                </div>
            </div>
        </div>
    </div>

<?php echo $footer; ?>