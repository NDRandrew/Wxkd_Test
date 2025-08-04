<?php
    @session_start();
    if($_SESSION['cod_usu']=='')
    {   
        ?>
        <script>
            window.location='login.php';
        </script>
        <?php
        die();
    }
    //$_SESSION['cod_usu']=6221076;

    // if ($_SESSION['cod_usu'] == 9407728) {
    // $_SESSION['cod_usu'] = 9335163;
    // }

?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">
<!-- Head -->
<head>
    <meta charset="utf-8" />
    <title>SISB</title>

    <meta name="description" content="Dashboard" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="shortcut icon" href="assets/img/favicon.png" type="image/x-icon">

    <!--Basic Styles-->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link id="bootstrap-rtl-link" href="" rel="stylesheet" />
    <link href="assets/css/font-awesome.min.css" rel="stylesheet" />
    <link href="assets/css/weather-icons.min.css" rel="stylesheet" />

    <!-- colorbox -->
    <link rel="stylesheet" href="http://10.222.217.12:4001/ERP/js/colorbox_1.4/colorbox-master/example3/colorbox.css" />

    <!-- Image Gallery -->
   

    <!--Fonts-->
    <link href="http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,400,600,700,300" rel="stylesheet" type="text/css">
    <link href='http://fonts.googleapis.com/css?family=Roboto:400,300' rel='stylesheet' type='text/css'>
 
    <!--Beyond styles-->
    <link id="beyond-link" href="assets/css/beyond.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/demo.min.css" rel="stylesheet" />
    <link href="assets/css/typicons.min.css" rel="stylesheet" />
    <link href="assets/css/animate.min.css" rel="stylesheet" />
    <link id="skin-link" href="" rel="stylesheet" type="text/css" />

    <!--Skin Script: Place this script in head to load scripts for skins and rtl support-->
    <script type="text/javascript" async="" src="https://www.googletagmanager.com/gtag/js?id=G-9KXDXGDS4L&amp;cx=c&amp;_slc=1"></script>
    <script async="" src="http://www.google-analytics.com/analytics.js"></script>
    <script src="assets/js/skins.min.js"></script>
    <link href="assets/css/skins/darkred.min.css" rel="stylesheet">

    <!--Page Related styles-->
    <link href="assets/css/dataTables.bootstrap.css" rel="stylesheet" />

    <style>
        .link-tr{
                cursor:pointer;
        }
        .thumbnail img { 
            min-height:200px; 
            height:200px; 
            min-width:100%;
        }
        .label-form-control{
           font-weight: bold;
        }
        .link_capa_default
        {
            cursor:pointer;
        }
        #tbody {
                cursor: move; /* fallback if grab cursor is unsupported */
                cursor: grab;
                cursor: -moz-grab;
                cursor: -webkit-grab;
            }

             /* (Optional) Apply a "closed-hand" cursor during drag operation. */
            #tbody:active { 
                cursor: grabbing;
                cursor: -moz-grabbing;
                cursor: -webkit-grabbing;
            }

        /* Chart Styles */
        .chart-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
            text-align: center;
        }
        
        .chart-row {
            margin-bottom: 30px;
        }
    </style>
    


</head>
<!-- /Head -->
<!-- Body -->
<body>
    <!-- Loading Container -->
    <div class="loading-container">
        <div class="loader"></div>
    </div>
    <!--  /Loading Container -->
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-inner">
            <div class="navbar-container">
                <!-- Navbar Barnd -->
                <div class="navbar-header pull-left">
                    <a href="#" class="navbar-brand">
                        <small>
                            <img src="assets/img/bradesco_expresso8.png" alt="" />
                        </small>
                    </a>
                </div>
                <!-- /Navbar Barnd -->
                <!-- Sidebar Collapse -->
                <div class="sidebar-collapse" id="sidebar-collapse">
                    <i class="collapse-icon fa fa-bars"></i>
                </div>
                <!-- /Sidebar Collapse -->
                <!-- Account Area and Settings -->
                <div class="navbar-header pull-right">
                    <div class="navbar-account">
                        <ul class="account-area">
                            <li>
                            </li>
                            <li>
                            </li>
                            <li>
                            <li>
                                <a class="login-area dropdown-toggle" data-toggle="dropdown">
                                    <div class="avatar" title="View your public profile">
                                        <img src="assets/img/avatars/default.jpg">
                                    </div>
                                    <section>
                                        <h2><span class="profile"><span><?php echo utf8_decode(ucwords($_SESSION['nome_usu'])); ?></span></span></h2>
                                    </section>
                                </a>
                                <!--Login Area Dropdown-->
                                <ul class="pull-right dropdown-menu dropdown-arrow dropdown-login-area">
                                    <li class="username"><a></a></li>
                                    <!--Avatar Area-->
                                    <li>
                                        <div class="avatar-area">
                                            <img src="assets/img/avatars/default.jpg" class="avatar">
                                            <span class="caption">Change Photo</span>
                                        </div>
                                    </li>
                                    <!--Avatar Area-->
                                    <li class="edit">
                                        <a href="profile.html" class="pull-left">Profile</a>
                                    </li>
                                    <!--Theme Selector Area-->
                                    <li class="theme-area">
                                        <ul class="colorpicker" id="skin-changer">
                                            <li><a class="colorpick-btn" href="#" style="background-color:#5DB2FF;" rel="assets/css/skins/blue.min.css"></a></li>
                                            <li><a class="colorpick-btn" href="#" style="background-color:#2dc3e8;" rel="assets/css/skins/azure.min.css"></a></li>
                                            <li><a class="colorpick-btn" href="#" style="background-color:#03B3B2;" rel="assets/css/skins/teal.min.css"></a></li>
                                            <li><a class="colorpick-btn" href="#" style="background-color:#53a93f;" rel="assets/css/skins/green.min.css"></a></li>
                                            <li><a class="colorpick-btn" href="#" style="background-color:#FF8F32;" rel="assets/css/skins/orange.min.css"></a></li>
                                            <li><a class="colorpick-btn" href="#" style="background-color:#cc324b;" rel="assets/css/skins/pink.min.css"></a></li>
                                            <li><a class="colorpick-btn" href="#" style="background-color:#AC193D;" rel="assets/css/skins/darkred.min.css"></a></li>
                                            <li><a class="colorpick-btn" href="#" style="background-color:#8C0095;" rel="assets/css/skins/purple.min.css"></a></li>
                                            <li><a class="colorpick-btn" href="#" style="background-color:#0072C6;" rel="assets/css/skins/darkblue.min.css"></a></li>
                                            <li><a class="colorpick-btn" href="#" style="background-color:#585858;" rel="assets/css/skins/gray.min.css"></a></li>
                                            <li><a class="colorpick-btn" href="#" style="background-color:#474544;" rel="assets/css/skins/black.min.css"></a></li>
                                            <li><a class="colorpick-btn" href="#" style="background-color:#001940;" rel="assets/css/skins/deepblue.min.css"></a></li>
                                        </ul>
                                    </li>
                                    <li class="dropdown-footer">
                                        <a href="logout.php">
                                            Logout
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                        <!-- Settings -->
                    </div>
                </div>
                <!-- /Account Area and Settings -->
            </div>
        </div>
    </div>
    <!-- /Navbar -->
    <!-- Main Container -->
    <div class="main-container container-fluid">
        <!-- Page Container -->
        <div class="page-container">
            <!-- Page Sidebar -->
            <div class="page-sidebar" id="sidebar">
                <!-- Page Sidebar Header-->
                <!-- /Page Sidebar Header -->
                <!-- Sidebar Menu -->
                <ul class="nav sidebar-menu submenu" style="margin-top:10px;">                    
                    <?php 
                        $_SESSION['cod_sistema'] = 30;
                        //include_once('\\\\mz-vv-fs-237/D4920/secoes/d4920s012/comum_s012/j/server2go/htdocs/erp/segmento/Gerenciamento_Acesso/view/menu.php');
                        include_once('\\\\mz-vv-fs-237/D4920/Secoes/D4920S012/Comum_S012/xampp/htdocs/ERP_V.01.02/segmento/Gerenciamento_acesso/view/menu.php');

                    ?>
                </ul>
                <!-- /Sidebar Menu -->
            </div>
            <!-- Page Content -->
            <div class="page-content" style="padding-left:20x;">
                <!-- Page Breadcrumb -->
                <div class="page-breadcrumbs">
                    <ul class="breadcrumb">
                        <li>
                            <i class="fa fa-home"></i>
                            <a href="#">Home</a>
                        </li>                     
                    </ul>
                </div>
                <!-- /Page Breadcrumb -->
                <!-- Page Header -->                                     
               <div class="return-ajax-acesso"> </div>
                <div  class="page-body">                                  
                    <div class="col-lg-12 col-sm-12 col-xs-12">
                        <div class="row return-ajax" id="indexDiv">
                            <!-- Charts Row -->
                            <div class="chart-row">
                                <!-- Pie Chart -->
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="chart-container">
                                        <div class="chart-title">Distribuição por Departamentos</div>
                                        <div id="pieChart" style="height: 400px;"></div>
                                    </div>
                                </div>
                                
                                <!-- Bar Chart -->
                                <div class="col-lg-6 col-md-6 col-sm-12">
                                    <div class="chart-container">
                                        <div class="chart-title">Vendas Mensais</div>
                                        <div id="barChart" style="height: 400px;"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Second Row of Charts -->
                            <div class="chart-row">
                                <!-- Additional Bar Chart -->
                                <div class="col-lg-12 col-md-12 col-sm-12">
                                    <div class="chart-container">
                                        <div class="chart-title">Performance por Trimestre</div>
                                        <div id="quarterlyChart" style="height: 400px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                            
                    </div>
                </div>    
            </div>
        </div>

<form role="form" id="formCapaQuiz" method="" action="" enctype="multipart/form-data">
    <input type="file" class="form-control" style="display:none;" name="modal_capa_quiz" id="modalCapaQuiz" placeholder="" />
    <input type="hidden" class="form-control" name="acao" value="inserirCapa"/>
</form>

<form role="form" id="formAnexoQuiz" method="" action="" enctype="multipart/form-data">
    <input type="file" class="form-control" style="display:none;" name="anexo_quiz" id="anexoQuiz" placeholder="" />
    <input type="hidden" class="form-control" name="acao" value="inserirAnexo"/>
</form>  


<!--Success Modal Templates-->
<div id="modal-alert-return-ajax" class="modal modal-message fade" data-backdrop='static' style="display: none;" aria-hidden="true">
    <div class="modal-dialog">
    </div> <!-- / .modal-dialog -->
</div>
<!--End Success Modal Templates-->


<button id="btn-modal-alert" style="display: none" class="btn btn-success" data-toggle="modal" data-target="#modal-return-ajax">Success</button>
<div id="modal-return-ajax" class="modal modal-message" data-backdrop="static" style="display: none; z-index: 999999;" aria-hidden="true"><!--data-backdrop="static"-->
    <div class="modal-dialog">
        
    </div> <!-- / .modal-dialog -->
</div>


<div id="" class="modal fade modal-projeto-status" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                <h4 class="modal-title return-ajax-modal-title-projeto-status" id="myLargeModalLabel">Status Projeto</h4>
            </div>
            <div class="modal-body return-ajax-modal-projeto-status">
                
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

<div id="" class="modal fade modal-tarefa-default" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                <h4 class="modal-title return-ajax-modal-title-default" id="myLargeModalLabel">Anexos</h4>
            </div>
            <div class="modal-body return-ajax-modal-tarefa-default">
                
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
<!--End Large Modal Templates-->


<!-- ================MODAL ENVIANDO======================= -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="enviando">
    <div class="modal-dialog modal-sm" style="width: 10%; margin-top: 300px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">X</button>
                <h4 class="modal-title" id="mySmallModalLabel"></h4>
            </div>
            <div class="modal-body">
                <div class="widget-body">
                    <div class="row">
                        <div class="col-xs-12" style="text-align: center;">                
                            <span id="spanEnviando"></span>             
                        </div>                        
                    </div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!--Fim-->

<!-- 
    ---------End Success Modal Templates-----------
 -->

    <!--Basic Scripts-->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/slimscroll/jquery.slimscroll.min.js"></script>

    <!--Beyond Scripts-->
    <script src="assets/js/beyond.min.js"></script>

    <!--Page Related Scripts-->
    <script src="assets/js/charts/sparkline/jquery.sparkline.js"></script>
    <script src="assets/js/charts/sparkline/sparkline-init.js"></script>

    <script src="assets/js/charts/easypiechart/jquery.easypiechart.js"></script>
    <script src="assets/js/charts/easypiechart/easypiechart-init.js"></script>

    <script src="assets/js/charts/morris/raphael-2.0.2.min.js"></script>
    <script src="assets/js/charts/morris/morris.js"></script>
    <script src="assets/js/charts/morris/morris-init.js"></script>

    <script src="assets/js/charts/flot/jquery.flot.js"></script>
    <script src="assets/js/charts/flot/jquery.flot.orderBars.js"></script>
    <script src="assets/js/charts/flot/jquery.flot.tooltip.js"></script>
    <script src="assets/js/charts/flot/jquery.flot.resize.js"></script>
    <script src="assets/js/charts/flot/jquery.flot.selection.js"></script>
    <script src="assets/js/charts/flot/jquery.flot.crosshair.js"></script>
    <script src="assets/js/charts/flot/jquery.flot.stack.js"></script>
    <script src="assets/js/charts/flot/jquery.flot.time.js"></script>
    <script src="assets/js/charts/flot/jquery.flot.pie.js"></script>

    <script src="assets/js/charts/chartjs/Chart.js"></script>


    <script src="assets/js/masked/jquery.mask.min.js"></script>
    <script src="assets/js/nestable/jquery.nestable.min.js"></script>


    <!-- Jquery Mask Money -->
    <script src="http://10.222.217.12:4001/erp/js/jquery.maskmoney.js"></script>

    <!--Page Related Scripts-->
    <script src="assets/js/datatable/jquery.dataTables.min.js"></script>
    <script src="assets/js/datatable/ZeroClipboard.js"></script>
    <script src="assets/js/datatable/dataTables.tableTools.min.js"></script>
    <script src="assets/js/datatable/dataTables.bootstrap.min.js"></script>
    <script src="assets/js/datatable/datatables-init.js"></script>


    <!--Page Related Scripts-->
    <script src="assets/js/fuelux/wizard/wizard-custom.js"></script>
    <script src="assets/js/toastr/toastr.js"></script>

    <!--Page Related Scripts-->
    <script src="assets/js/MascaraDados.js"></script>
    <script src="assets/js/script_inventario.js"></script>
    <script src="assets/js/script_vpn.js"></script>
    <script src="assets/js/script_vpn_usuario.js"></script>

    <!-- SISTEMA FORMULARIO VITOR -->
    <script src="assets/js/form.js"></script>

    <!-- SISTEMA LUCAS VAZ MAIA INFRA -->
    <script src="../assets/js/controlAcessoPasta.js"></script>
    <script src="../assets/js/cadastroSolicPedidos.js"></script>
    <script src="../assets/js/alterarPedidos.js"></script>

    <!-- Highcharts Library -->
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>

    <!-- Custom Charts Script -->
    <script src="assets/js/custom-charts.js"></script>

<!--  /Body -->
</html>

-----------------


// Custom Charts for SISB Dashboard
// File: assets/js/custom-charts.js

$(document).ready(function() {
    
    // Common chart options
    var commonOptions = {
        credits: {
            enabled: false
        },
        exporting: {
            enabled: true,
            buttons: {
                contextButton: {
                    menuItems: ['downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG']
                }
            }
        }
    };

    // Pie Chart - Department Distribution
    var pieChartOptions = $.extend(true, {}, commonOptions, {
        chart: {
            type: 'pie',
            backgroundColor: 'transparent'
        },
        title: {
            text: null
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b><br/>Valor: <b>{point.y}</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                },
                showInLegend: true,
                colors: ['#AC193D', '#5DB2FF', '#53a93f', '#FF8F32', '#8C0095', '#03B3B2', '#cc324b']
            }
        },
        series: [{
            name: 'Departamentos',
            data: [
                {
                    name: 'Tecnologia',
                    y: 45,
                    sliced: true,
                    selected: true
                },
                {
                    name: 'Vendas',
                    y: 25
                },
                {
                    name: 'Marketing',
                    y: 15
                },
                {
                    name: 'RH',
                    y: 8
                },
                {
                    name: 'Financeiro',
                    y: 7
                }
            ]
        }]
    });

    // Bar Chart - Monthly Sales
    var barChartOptions = $.extend(true, {}, commonOptions, {
        chart: {
            type: 'column',
            backgroundColor: 'transparent'
        },
        title: {
            text: null
        },
        xAxis: {
            categories: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
            crosshair: true
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Vendas (R$ mil)'
            }
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                '<td style="padding:0"><b>R$ {point.y:.1f}k</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0,
                dataLabels: {
                    enabled: true,
                    format: 'R$ {point.y}k'
                }
            }
        },
        colors: ['#AC193D', '#5DB2FF'],
        series: [{
            name: '2024',
            data: [150, 180, 220, 190, 240, 280, 320, 290, 250, 310, 340, 380]
        }, {
            name: '2023',
            data: [120, 140, 160, 150, 180, 210, 240, 220, 200, 230, 260, 290]
        }]
    });

    // Quarterly Performance Chart
    var quarterlyChartOptions = $.extend(true, {}, commonOptions, {
        chart: {
            type: 'line',
            backgroundColor: 'transparent'
        },
        title: {
            text: null
        },
        xAxis: {
            categories: ['Q1 2023', 'Q2 2023', 'Q3 2023', 'Q4 2023', 'Q1 2024', 'Q2 2024', 'Q3 2024', 'Q4 2024']
        },
        yAxis: {
            title: {
                text: 'Performance (%)'
            }
        },
        tooltip: {
            valueSuffix: '%'
        },
        plotOptions: {
            line: {
                dataLabels: {
                    enabled: true,
                    format: '{point.y}%'
                },
                enableMouseTracking: true
            }
        },
        colors: ['#AC193D', '#5DB2FF', '#53a93f'],
        series: [{
            name: 'Meta',
            data: [85, 88, 90, 92, 85, 88, 90, 92]
        }, {
            name: 'Realizado',
            data: [82, 91, 87, 89, 88, 94, 92, 95]
        }, {
            name: 'Projeção',
            data: [80, 85, 88, 90, 85, 90, 94, 97]
        }]
    });

    // Initialize charts when page loads
    function initializeCharts() {
        try {
            // Check if containers exist before creating charts
            if ($('#pieChart').length) {
                Highcharts.chart('pieChart', pieChartOptions);
            }
            
            if ($('#barChart').length) {
                Highcharts.chart('barChart', barChartOptions);
            }
            
            if ($('#quarterlyChart').length) {
                Highcharts.chart('quarterlyChart', quarterlyChartOptions);
            }
            
            console.log('Charts initialized successfully');
        } catch (error) {
            console.error('Error initializing charts:', error);
        }
    }

    // Initialize charts
    initializeCharts();

    // Responsive handling
    $(window).resize(function() {
        setTimeout(function() {
            // Redraw charts on window resize
            if (window.Highcharts) {
                $.each(Highcharts.charts, function(i, chart) {
                    if (chart) {
                        chart.reflow();
                    }
                });
            }
        }, 100);
    });

    // Function to update pie chart data (for future use with AJAX)
    window.updatePieChart = function(newData) {
        var chart = Highcharts.charts.find(function(chart) {
            return chart && chart.renderTo.id === 'pieChart';
        });
        
        if (chart) {
            chart.series[0].setData(newData);
        }
    };

    // Function to update bar chart data (for future use with AJAX)
    window.updateBarChart = function(categories, series) {
        var chart = Highcharts.charts.find(function(chart) {
            return chart && chart.renderTo.id === 'barChart';
        });
        
        if (chart) {
            chart.xAxis[0].setCategories(categories);
            chart.series[0].setData(series[0].data);
            if (series[1]) {
                chart.series[1].setData(series[1].data);
            }
        }
    };

    // Function to refresh all charts
    window.refreshCharts = function() {
        initializeCharts();
    };

});

// Utility function to generate random data for testing
function generateRandomData(count, min, max) {
    var data = [];
    for (var i = 0; i < count; i++) {
        data.push(Math.floor(Math.random() * (max - min + 1)) + min);
    }
    return data;
}

// Function to export all charts as images (optional feature)
function exportAllCharts() {
    if (window.Highcharts) {
        $.each(Highcharts.charts, function(i, chart) {
            if (chart) {
                chart.exportChart({
                    type: 'image/png',
                    filename: 'chart-' + (i + 1)
                });
            }
        });
    }
}