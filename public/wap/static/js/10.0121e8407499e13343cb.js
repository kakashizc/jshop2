webpackJsonp([10],{FfIq:function(e,t){},"HWY/":function(e,t){},LwQL:function(e,t){},dLxA:function(e,t,i){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var s={props:{goods:{type:[Array,Object],default:function(){return[]}},delivery:{type:Boolean,default:function(){return!1}}},computed:{checked:{get:function(){var e=[];for(var t in this.goods)e.push(this.goods[t].id);return e},set:function(e){this.$emit("checked",e)}}}},a={render:function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("div",{staticClass:"afterservicelist"},[i("yd-checklist",{attrs:{label:!0,color:"#ff3b44"},model:{value:e.checked,callback:function(t){e.checked=t},expression:"checked"}},e._l(e.goods,function(t,s){return i("yd-checklist-item",{key:s,attrs:{val:t.id,disabled:!e.delivery}},[i("yd-flexbox",{staticStyle:{padding:".2rem 0"}},[i("img",{staticClass:"goodsimg",attrs:{src:t.image_url}}),e._v(" "),i("yd-flexbox-item",{attrs:{align:"top"}},[i("h3",{staticClass:"goodsname"},[e._v(e._s(t.name))]),e._v(" "),i("p",{staticClass:"standard"},[e._v(e._s(t.addon))])])],1)],1)}))],1)},staticRenderFns:[]},r=i("C7Lr")(s,a,!1,null,null,null).exports,n={data:function(){return{checkedRadio:"1",custPrice:""}},props:{delivery:{type:Boolean,default:function(){return!0}},price:{type:[String,Number],default:function(){return""}}},watch:{checkedRadio:function(){this.$emit("type",this.checkedRadio)},custPrice:function(){this.$emit("custPrice",this.custPrice)}}},o={render:function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("yd-cell-group",[i("yd-cell-item",[i("span",{attrs:{slot:"left"},slot:"left"},[e._v("售后类型")]),e._v(" "),i("span",{attrs:{slot:"right"},slot:"right"},[i("div",{staticClass:"service-type"},[i("input",{directives:[{name:"model",rawName:"v-model",value:e.checkedRadio,expression:"checkedRadio"}],attrs:{type:"radio",id:"tk",value:"1"},domProps:{checked:e._q(e.checkedRadio,"1")},on:{change:function(t){e.checkedRadio="1"}}}),e._v(" "),i("label",{attrs:{for:"tk"}},[e._v("仅退款")])]),e._v(" "),e.delivery?i("div",{staticClass:"service-type"},[i("input",{directives:[{name:"model",rawName:"v-model",value:e.checkedRadio,expression:"checkedRadio"}],attrs:{type:"radio",id:"thtk2",value:"2"},domProps:{checked:e._q(e.checkedRadio,"2")},on:{change:function(t){e.checkedRadio="2"}}}),e._v(" "),i("label",{attrs:{for:"thtk2"}},[e._v("退货退款")])]):i("div",{staticClass:"service-type disabled-input"},[i("input",{directives:[{name:"model",rawName:"v-model",value:e.checkedRadio,expression:"checkedRadio"}],attrs:{type:"radio",id:"thtk",value:"2",disabled:""},domProps:{checked:e._q(e.checkedRadio,"2")},on:{change:function(t){e.checkedRadio="2"}}}),e._v(" "),i("label",{attrs:{for:"thtk"}},[e._v("退货退款")])])])]),e._v(" "),i("yd-cell-item",[i("span",{attrs:{slot:"left"},slot:"left"},[e._v("退款金额")]),e._v(" "),"1"===e.checkedRadio?i("yd-input",{attrs:{slot:"right",type:"number",placeholder:e.price+"元",readonly:""},slot:"right"}):"2"===e.checkedRadio?i("yd-input",{attrs:{slot:"right",type:"number",placeholder:"请输入退款金额"},slot:"right",model:{value:e.custPrice,callback:function(t){e.custPrice=t},expression:"custPrice"}}):e._e()],1)],1)},staticRenderFns:[]};var c={data:function(){return{imgs:[],upload:!0,imgData:{accept:"image/gif, image/jpeg, image/png, image/jpg"}}},computed:{uploadImageMax:function(){return this.$store.state.config.upload_image_max}},methods:{uploadImg:function(e){var t=this,i=e.target.files[0],s=new FormData;s.append("upfile",i,i.name),this.$api.uploadFile(s,function(e){e.status&&t.imgs.push(e.data)}),this.$refs.file[0].value=""},remove:function(e){this.imgs.splice(e,1)}},watch:{imgs:function(){this.$emit("images",this.imgs),this.imgs.length>=this.uploadImageMax?this.upload=!1:this.upload=!0}}},d={render:function(){var e=this,t=e.$createElement,s=e._self._c||t;return s("div",{staticClass:"afterserviceimg"},[e._l(e.imgs,function(t,i){return e.imgs.length?s("div",{key:i,staticClass:"uploadimg-list"},[s("yd-badge",{nativeOn:{click:function(t){e.remove(i)}}},[e._v("X")]),e._v(" "),s("img",{staticClass:"thumbnail-list",attrs:{src:t.url}})],1):e._e()}),e._v(" "),s("div",{directives:[{name:"show",rawName:"v-show",value:e.upload,expression:"upload"}],staticClass:"uploadimg"},[s("input",{ref:"file",attrs:{name:"file",type:"file",accept:"image/png,image/gif,image/jpeg"},on:{change:e.uploadImg}}),e._v(" "),s("img",{attrs:{slot:"icon",src:i("jd4r")},slot:"icon"})])],2)},staticRenderFns:[]},l={render:function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("div",{staticClass:"afterservicetext"},[i("yd-cell-group",{attrs:{title:"问题描述"}},[i("yd-cell-item",[i("yd-textarea",{attrs:{slot:"right",placeholder:"请您在此描述您的问题",maxlength:"100"},slot:"right",model:{value:e.reason,callback:function(t){e.reason=t},expression:"reason"}})],1)],1)],1)},staticRenderFns:[]};var u={components:{afterservicelist:r,afterservicecell:i("C7Lr")(n,o,!1,function(e){i("LwQL")},null,null).exports,afterserviceimg:i("C7Lr")(c,d,!1,null,null,null).exports,afterservicetext:i("C7Lr")({data:function(){return{reason:""}},watch:{reason:function(){this.$emit("reason",this.reason)}}},l,!1,function(e){i("FfIq")},null,null).exports},data:function(){return{order_id:this.$route.query.order_id||0,is_delivery:!0,goodsList:[],type:"1",img_id:[],items:[],reasonMsg:"",afterPrice:"",inputPrice:""}},created:function(){var e=this;if(!this.order_id)return this.$dialog.alert({mes:"订单不存在"}),!1;this.$api.afterSalesStatus({order_id:this.order_id},function(t){if(t.status){var i=t.data.text_status;if("pending_payment"!==i&&"completed"!==i&&"cancel"!==i)for(var s in"pending_delivery"===i&&(e.is_delivery=!1),e.goodsList=t.data.items,e.afterPrice=t.data.payed-t.data.refunded,e.goodsList)e.items=e.items.concat({id:e.goodsList[s].id,nums:e.goodsList[s].nums});else e.$dialog.alert({mes:"该订单状态不可申请售后",callback:function(){e.$router.go(-1)}})}else e.$dialog.alert({mes:"该订单不存在",callback:function(){e.$router.go(-1)}})})},methods:{checked:function(e){var t=[];for(var i in e)for(var s in this.goodsList)e[i]===this.goodsList[s].id&&t.push({id:this.goodsList[s].id,nums:this.goodsList[s].nums});this.items=t},images:function(e){for(var t in e)-1===this.img_id.indexOf(e[t].image_id)&&this.img_id.push(e[t].image_id)},reason:function(e){this.reasonMsg=e},custPrice:function(e){this.inputPrice=e},afterType:function(e){this.type=e},formSubmit:function(){var e=this,t={order_id:this.order_id,type:this.type,images:this.img_id,items:this.items,refund:this.afterPrice,reason:this.reasonMsg};"2"===this.type&&(t.refund=this.inputPrice),this.$api.addAfterSales(t,function(t){t.status&&e.$dialog.toast({mes:"提交成功",time:1500,icon:"success",callback:function(){e.$router.go(-1)}})})}}},f={render:function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("div",{staticClass:"afterservice"},[i("afterservicelist",{attrs:{goods:e.goodsList,delivery:e.is_delivery},on:{checked:e.checked}}),e._v(" "),i("afterservicecell",{attrs:{delivery:e.is_delivery,price:e.afterPrice},on:{type:e.afterType,custPrice:e.custPrice}}),e._v(" "),i("afterserviceimg",{on:{images:e.images}}),e._v(" "),i("afterservicetext",{on:{reason:e.reason}}),e._v(" "),i("yd-button-group",[i("yd-button",{staticStyle:{"max-width":"750px"},attrs:{size:"large",bgcolor:"#ff3b44",color:"#fff"},nativeOn:{click:function(t){return e.formSubmit(t)}}},[e._v("提交")])],1)],1)},staticRenderFns:[]};var m=i("C7Lr")(u,f,!1,function(e){i("HWY/")},null,null);t.default=m.exports},jd4r:function(e,t,i){e.exports=i.p+"static/img/addimg.0d12f6a.png"}});
//# sourceMappingURL=10.0121e8407499e13343cb.js.map