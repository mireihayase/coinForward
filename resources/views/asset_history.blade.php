<!DOCTYPE html>
<html>

@include('include/head')
<script src="http://www.chartjs.org/dist/2.7.1/Chart.bundle.js"></script>
<script src="http://www.chartjs.org/samples/latest/utils.js"></script>

{{--<script src="../js/coin_rate_chart.js"></script>--}}

<body>
@include('include/header')

@include('include/sidebar')

<div class="main-contents">
    <div class="main-contents__body">

        <dl class="panel"><dt class="summary__head"><i class="fa fa-user"></i><span>総資産</span></dt>
            <dd class="summary__body"><span class="summary__num">{{number_format($total_amount)}} 円</span>
                <div id="canvas-holder" style="width:100%">
                    <canvas id="chart-area" />
                </div>
            </dd>
        </dl>

            <div id="canvas-holder" style="width:100%">
                <canvas id="chart-area" />
            </div>
    </div>


</div>
<script src="./bower_components/jquery/dist/jquery.min.js"></script>
<script src="./bower_components/adminize/js/adminize.min.js"></script>

<script src="../js/asset_history.js"></script>
</body>

</html>