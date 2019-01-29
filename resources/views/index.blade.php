@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{__('app.type_'.$type)}}拦截查询
                    </div>
                    <div class="card-body">
                        @if($type == 'whois')
                            <input type="text" id="domain" class="form-control" name="domains" placeholder="请输入要查询的域名">
                            <button type="button" class="btn btn-success search-btn" onclick="whois()">查询
                            </button>
                            <div id="whois" style="line-height: 2;padding: 20px;white-space: pre;display: none"></div>
                        @else
                            <textarea id="domains" class="form-control" rows="5" name="domains"
                                      placeholder="请输入要查询的域名，一行一个"></textarea>
                            <button type="button" id="search" class="btn btn-success search-btn" onclick="search()">查询
                            </button>
                            <table class="table table-bordered table-bg mgt-20" id="result" style="display: none">
                                <thead>
                                <tr>
                                    <th>域名正常</th>
                                    <th>域名拦截</th>
                                    <th>检测失败</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr style="height: 48px">
                                    <td id="success"></td>
                                    <td id="intercept"></td>
                                    <td id="fail"></td>
                                </tr>
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{asset('js/jquery.min.js')}}"></script>
    <script>
        function search() {
            $('#success').html('');
            $('#intercept').append('');
            $('#fail').append('');
            var domains = ($('#domains').val()).split(/[(\r\n)\r\n]+/);
            if (domains) {
                for (var i = 0; i < domains.length; i++) {
                    check(domains[i])
                }
            }
        }
        function check(domain) {
            $.ajax({
                type: 'post',
                url: '/api/tools/{{$type}}',
                data: {
                    domain: domain,
                    timestamp: '{{$timestamp}}',
                    token: '{{$token}}'
                },
                success: function (data) {
                    var intercept = data.data.intercept;
                    if (intercept == 1) {
                        $('#success').append(domain + '<br/>')
                    } else if (intercept == 2) {
                        $('#intercept').append(domain + '<br/>')
                    } else {
                        $('#fail').append(domain + '<br/>')
                    }
                    $('#result').show();
                },
                error: function (resp) {
                    $('#fail').append(domain + '<br/>');
                    $('#result').show();
                }
            });
        }

        function whois() {
            var domain = $('#domain').val();
            $.ajax({
                type: 'post',
                url: '/api/tools/{{$type}}',
                data: {
                    domain: domain,
                    timestamp: '{{$timestamp}}',
                    token: '{{$token}}'
                },
                success: function (data) {
                    var result = data.whois_info.info;
                    $('#whois').append('text').html(result).show();
                },
                error: function (resp) {
                    alert('查询失败')
                }
            });
        }
    </script>
@endsection