webpackJsonp([40],{DIsE:function(t,i,e){"use strict";Object.defineProperty(i,"__esModule",{value:!0});var s=e("IHPB"),a=e.n(s),l={data:function(){return{page:1,pageSize:10,typeId:this.$route.query.type_id||"",articleList:[]}},mounted:function(){var t=this;this.typeId?this.getArticleList():this.$dialog.alert({mes:"该文章分类不存在",callback:function(){t.$router.go(-1)}})},methods:{getArticleList:function(){var t=this;this.$api.articleList({page:this.page,limit:this.pageSize,type_id:this.typeId},function(i){var e=t.dateFormat(i.data.list);t.articleList=[].concat(a()(e)),e.length<t.pageSize&&t.$refs.infinitescrollDemo.$emit("ydui.infinitescroll.loadedDone")})},loadMore:function(){var t=this;this.$api.articleList({page:++this.page,limit:this.pageSize,type_id:this.typeId},function(i){var e=t.dateFormat(i.data.list);t.articleList=[].concat(a()(t.articleList),a()(e)),e.length<t.pageSize?t.$refs.infinitescrollDemo.$emit("ydui.infinitescroll.loadedDone"):t.$refs.infinitescrollDemo.$emit("ydui.infinitescroll.finishLoad")})},dateFormat:function(t){var i=this;return t.forEach(function(t){t.ctime=i.GLOBAL.timeToDate(t.ctime)}),t},showDetail:function(t){this.$router.push({path:"/article",query:{article_id:t}})}}},r={render:function(){var t=this,i=t.$createElement,e=t._self._c||i;return e("div",{staticClass:"articlelist"},[e("yd-infinitescroll",{ref:"infinitescrollDemo",attrs:{callback:t.loadMore}},t._l(t.articleList,function(i,s){return e("div",{key:s,staticClass:"articlelist-item",attrs:{slot:"list"},on:{click:function(e){t.showDetail(i.id)}},slot:"list"},[e("img",{attrs:{src:i.cover,alt:""}}),t._v(" "),e("div",{staticClass:"articlelist-item-body"},[e("h3",{staticClass:"articlelist-title"},[t._v(t._s(i.title))]),t._v(" "),e("p",{staticClass:"articlelist-time"},[t._v(t._s(i.ctime))])])])}))],1)},staticRenderFns:[]};var n=e("C7Lr")(l,r,!1,function(t){e("pnst")},null,null);i.default=n.exports},pnst:function(t,i){}});
//# sourceMappingURL=40.a0bbb766173e24a61705.js.map