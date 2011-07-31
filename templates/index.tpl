{literal}
        <script type="text/javascript">
            $(function() {
                // id of Cytoscape Web container div
                var div_id = "cytoscapeweb";
                $('#'+div_id).height($(document).height());
                $('#'+div_id).width($(document).width()*0.95);

                {/literal}
                var graph = {$graph};
                {literal}
                graph.dataSchema = {
                    		nodes: [ { name: "visits", type: "double" },
                                         { name: "label", type: "string" },
                                         { name: "bounce", type: "double" } ],
                                edges: [ { name: "weight", type: "double" } ]
                    	};
                // visual style we will use
                var visual_style = {
                    global: {
                        backgroundColor: "#FFFFFF"
                    } ,
                    nodes: {
                        borderWidth: 1,
                        borderColor: "#ffffff",
                        size: {
                            continuousMapper: { attrName: "visits", minValue: 20, maxValue: 200 }
                        } ,
                        color: {
                            continuousMapper: { attrName: "bounce", minValue: '#0080FF', maxValue: '#FF0000', minAttrValue: 0, maxAttrValue: 1  }
                        } ,
                        labelHorizontalAnchor: "center"
                    } ,
                    edges: {
                        width: {
                            defaultValue: 1,
                            continuousMapper: { attrName: "weight", minValue: 1, maxValue: 50 }
                        },
                        opacity: {
                            defaultValue: 0.1,
                            continuousMapper: { attrName: "weight", minValue: 0.2, maxValue: 0.8 }
                        },
                        targetArrowShape: "DELTA",
                        color: "#000000"
                    }
                } ;

                // initialization options
                var options = {
                    swfPath: "plugins/Trails/templates/swf/CytoscapeWeb",
                    flashInstallerPath: "plugins/Trails/templates/swf/playerProductInstall"
                };

                var vis = new org.cytoscapeweb.Visualization(div_id, options);

                vis.ready(function() {
                    $('#cytoscapeweb').mousewheel(function(event, delta, deltaX, deltaY) {
                            console.log(event);
                            vis.zoom(vis.zoom() + (deltaY / 10));
                            return false;
                    });
                    $.scrollTo('#cytoscapeweb', 400);
                    
                });

                //console.log(graph);

                var draw_options = {
                    // your data goes here
                    network: graph,

                    // let's try another layout
                    layout: "Circle",

                    // set the style at initialisation
                    visualStyle: visual_style,
                    mouseDownToDragDelay: 10,

                    // hide pan zoom
                    //panZoomControlVisible: false
                };

                vis.draw(draw_options);
            });
        </script>
        
        <style>

            /* The Cytoscape Web container must have its dimensions set. */
            #cytoscapeweb { width: 100%; height: 100%; margin: 0px; padding: 0px;}
        </style>
        

        <div id="cytoscapeweb">
            Cytoscape Web will replace the contents of this div with your graph.
        </div>
{/literal}