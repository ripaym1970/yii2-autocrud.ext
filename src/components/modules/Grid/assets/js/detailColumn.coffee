Backbone.Radio.channel("widget").on 'render', (view)->
  view.$el.kendoTooltip {
    filter: '.js-property-icon',
    content: {
      url: "/"
    },
    width: 500,
    position: 'left',
    requestStart: (e)->
      e.options.url = e.target.data('href')
  }
