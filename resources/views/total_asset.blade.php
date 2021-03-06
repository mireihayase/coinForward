<!DOCTYPE html>
<html>

  @include('include/head')


  <body>
    @include('include/header')

    @include('include/sidebar')

    <div class="main-contents">
      <div class="main-contents__body">
        <ul class="breadcrumbs">
          <li class="breadcrumbs__list"><a href="">Home</a></li>
        </ul>
        <h1 class="page-header" style="margin-bottom: 0px;"><i class="fa fa-file-text"></i><span>Top</span></h1>


        <div class="summaries">
          <a href="/coin_ratio">
            <dl class="panel"><dt class="summary__head"><i class="fa fa-user"></i><span>総資産</span></dt>
              <dd class="summary__body"><span class="summary__num">{{number_format($total_amount)}} 円</span>
                {{--<span class="summary__num diff success">+264(+400%)</span>--}}
              </dd>
            </dl>
          </a>
          <a href="/asset_history">
            <dl class="panel"><dt class="summary__head"><i class="fa fa-film"></i><span>日次損益</span></dt>
              <dd class="summary__body"><span class="summary__num">{{number_format($daily_gain)}}</span>
                {{--<span class="summary__num diff danger">-1,000(-100%)</span>--}}
              </dd>
            </dl>
          </a>
        </div>


        <p class="weight--bold">保有資産</p>
        @if(empty($all_assets))
          API連携なし
        @else
          <table class="table table--striped">
            <thead>
              <tr>
                <th>銘柄</th>
                <th>保有数</th>
                <th>JPY換算</th>
              </tr>
            </thead>
            <tbody>
              @foreach($all_assets as $exchange => $assets)
                <tr style="background-color: gray; color:white;"><td>{{$exchange}}</td><td></td><td>{{number_format($assets['total'])}}</td></tr>
                @if(!empty($assets['coin']))
                  @foreach($assets['coin'] as $v)
                    <tr>
                      <td>{{$v['coin_name']}}</td>
                      <td>{{number_format($v['amount'], 2)}}</td>
                      <td>
                        @if(!empty($v['convert_JPY']))
                          {{number_format($v['convert_JPY'], 2)}}
                        @else
                          0
                        @endif
                      </td>
                    </tr>
                  @endforeach
                @else
                  <tr>
                    <td>所有コインなし</td>
                    <td></td>
                    <td></td>
                  </tr>
                @endif
              @endforeach
            </tbody>
          </table>
        @endif
        </div>
      </div>
    <script src="./bower_components/jquery/dist/jquery.min.js"></script>
    <script src="./bower_components/adminize/js/adminize.min.js"></script>
  </body>

</html>