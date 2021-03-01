<!DOCTYPE html>
<html>
<head>
    <title>Domain Tool</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link rel="stylesheet" href="{{ URL::asset('css/vant.css')}}">
    <link rel="stylesheet" href="{{ URL::asset('css/index.css')}}">
    <script type="text/javascript" src="{{ URL::asset('js/vue.min.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/axios.min.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/vant.min.js') }}"></script>
</head>
<body>
<div id="app">
    <van-nav-bar title="Domain Tool" right-text="GitHub" @click-right="jumpGitHub"></van-nav-bar>
    <van-tabs color="#1890FF">
        <van-tab title="拦 截 查 询">
            <van-cell class="block">
                <van-form ref="formData">
                    <van-field v-model="domain" rows="6" autosize type="textarea" placeholder="请输入域名，一行一个"></van-field>
                    <van-button type="primary" size="large" @click="check('wechat')">微信查询</van-button>
                    <van-button type="info" size="large" @click="check('qq')">QQ 查询</van-button>
                </van-form>
            </van-cell>
            <van-cell class="block" v-if="show">
                <van-grid :column-num="3">
                    <van-grid-item icon="success" text="正常"></van-grid-item>
                    <van-grid-item icon="cross" text="拦截"></van-grid-item>
                    <van-grid-item icon="fail" text="查询失败"></van-grid-item>
                </van-grid>
                <van-grid :column-num="3">
                    <van-grid-item v-html="normal"></van-grid-item>
                    <van-grid-item v-html="abnormal"></van-grid-item>
                    <van-grid-item v-html="fail"></van-grid-item>
                </van-grid>
            </van-cell>
        </van-tab>
        <van-tab title="接口文档">
            <iframe src="/api/doc" width="100%" height="900px" frameborder="0"></iframe>
        </van-tab>
    </van-tabs>
</div>
</body>
<script>
    var vue = new Vue({
        el: '#app',
        delimiters: ['[[', ']]'],
        data() {
            return {
                show: false,
                domain: '',
                normal: '',
                abnormal: '',
                fail: '',
                list: {data: []},
            };
        },
        methods: {
            check(type) {
                let me = this;
                let domain = Array.from(new Set((me.domain).split(/[(\r\n)\r\n]+/)));
                if (!this.domain) {
                    vant.Toast('请输入要查询的域名！');
                    return;
                } else if (domain.length > 50) {
                    vant.Toast('域名最多不超过50个！');
                    return;
                }
                me.show = true;
                me.normal = me.abnormal = me.fail = '';
                for (let item of domain) {
                    axios.post('/api/tools/' + type, {domain: item}).then(function (rsp) {
                        let data = rsp.data.data;
                        if (data.intercept == 1) {
                            me.normal += item + '<br/>';
                        } else if (data.intercept == 2) {
                            me.abnormal += item + '<br/>';
                        } else {
                            me.fail += item + '<br/>';
                        }
                    }).catch(function (rsp) {
                        vant.Toast('请求异常！');
                    });
                }
            },
            jumpGitHub() {
                window.open('https://github.com/GallopYD/domain-tool');
            }
        },
    });
    Vue.use(vant.Lazyload);
</script>
</html>
