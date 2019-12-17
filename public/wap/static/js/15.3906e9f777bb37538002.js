webpackJsonp([15],{"+vSD":function(t,s,i){"use strict";Object.defineProperty(s,"__esModule",{value:!0});var e=i("ZLEe"),n=i.n(e),a={name:"OrderVerification",data:function(){return{id:this.$route.query.id,keys:"",info:{},disabled:!1}},mounted:function(){this.id&&(this.keys=this.id,this.getLadingInfo())},computed:{isDisabled:function(){return!this.keys||!n()(this.info).length}},methods:{getLadingInfo:function(){var t=this;this.$api.ladingInfo({key:this.keys},function(s){t.info=s.data,s.status||t.$dialog.alert({mes:"提货单不存在!"})})},searchHandler:function(){if(!this.keys)return this.$dialog.toast({mes:"请输入提货单号、订单号、提货人手机号进行查询",timeout:1300}),!1;this.getLadingInfo()},verification:function(t){var s=this;this.$dialog.confirm({title:"提示",mes:"确认执行提货单核销操作吗?",opts:function(){s.$api.ladingExec({lading_id:s.info.id},function(t){t.status&&s.$dialog.toast({mes:t.msg,timeout:1300,callback:function(){s.getLadingInfo()}})})}})}}},o={render:function(){var t=this,s=t.$createElement,e=t._self._c||s;return e("div",{staticClass:"searchpage"},[e("div",{staticClass:"searchpage-header"},[e("div",{staticClass:"search-input"},[e("input",{directives:[{name:"model",rawName:"v-model",value:t.keys,expression:"keys"}],attrs:{type:"text",placeholder:"请输入提货单号、订单号、提货人手机号"},domProps:{value:t.keys},on:{keyup:function(s){return"button"in s||!t._k(s.keyCode,"enter",13,s.key,"Enter")?t.searchHandler(s):null},input:function(s){s.target.composing||(t.keys=s.target.value)}}})]),t._v(" "),e("button",{staticClass:"searchimg",on:{click:t.searchHandler}},[e("img",{attrs:{src:i("cmkS")}})])]),t._v(" "),e("div",{staticClass:"searchpage-body"},[Object.keys(t.info).length?e("div",[e("p",[t._v("提货人: "+t._s(t.info.name))]),t._v(" "),e("p",[t._v("联系电话: "+t._s(t.info.mobile))]),t._v(" "),e("yd-list",{attrs:{theme:"4"}},t._l(t.info.goods,function(s,i){return e("yd-list-item",{key:i},[e("img",{attrs:{slot:"img",src:s.image_url},slot:"img"}),t._v(" "),e("h3",{staticClass:"goodsname",attrs:{slot:"title"},slot:"title"},[t._v(t._s(s.name))]),t._v(" "),e("p",{staticClass:"goods",attrs:{slot:"title"},slot:"title"},[t._v(t._s(s.addon))]),t._v(" "),e("p",{staticClass:"goods",attrs:{slot:"title"},slot:"title"},[t._v("BN: "+t._s(s.bn))]),t._v(" "),e("p",{staticClass:"goods",attrs:{slot:"title"},slot:"title"},[t._v("SN: "+t._s(s.sn))]),t._v(" "),e("yd-list-other",{attrs:{slot:"other"},slot:"other"},[e("div",[e("span",{staticClass:"demo-list-price"},[e("em",[t._v("¥")]),t._v(t._s(s.price))])]),t._v(" "),e("div",[t._v("x "+t._s(s.nums))])])],1)}))],1):t._e()]),t._v(" "),e("yd-button",{attrs:{size:"large",type:"danger",disabled:t.isDisabled},nativeOn:{click:function(s){t.verification(t.info.id)}}},[t._v("确认核销")])],1)},staticRenderFns:[]};var r=i("C7Lr")(a,o,!1,function(t){i("yx7B")},"data-v-ac20a20e",null);s.default=r.exports},cmkS:function(t,s){t.exports="data:image/png;base64,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"},yx7B:function(t,s){}});
//# sourceMappingURL=15.3906e9f777bb37538002.js.map