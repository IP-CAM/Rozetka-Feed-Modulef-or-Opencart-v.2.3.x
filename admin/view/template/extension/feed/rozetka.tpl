<?php echo $header; ?><?php echo $column_left; ?>
    <div id="content">
        <div class="page-header">
            <div class="container-fluid">
                <div class="pull-right">
                    <button type="submit" form="form-rozetka" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary btn-lg">
                        <i class="fa fa-save"></i> <?php echo $button_save; ?>
                    </button>
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
                    <a href="#tab-history" data-toggle="tab">
                        <i class="fa fa-history"></i> История генераций
                    </a>
                </li>
                <li>
                    <a href="#tab-mapping" data-toggle="tab">
                        <i class="fa fa-link"></i> Маппинг категорий
                    </a>
                </li>
            </ul>

            <div class="tab-content modern-tab-content">
                <div class="tab-pane active" id="tab-dashboard">
					<?php include("rozetka/statistics.tpl"); ?>

					<?php include("rozetka/control.tpl"); ?>
                </div>

                <div class="tab-pane" id="tab-settings">
                    <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-rozetka">
						<?php include("rozetka/base_setting.tpl"); ?>
                    </form>
                </div>

                <div class="tab-pane" id="tab-filters">
					<?php include("rozetka/filter.tpl"); ?>
                </div>

                <div class="tab-pane" id="tab-history">
					<?php include("rozetka/history.tpl"); ?>
                </div>

                <div class="tab-pane" id="tab-mapping">
					<?php include("rozetka/category_mapping.tpl"); ?>
                </div>
            </div>
        </div>
    </div>

<?php echo $footer; ?>