# GIS Plugin

The **Gis** Plugin is an extension for [Grav CMS](http://github.com/getgrav/grav). This plugin, using the Leaflet javascript library, aims to provide a simple way to geolocate contents and to display interactive maps.

## Installation

<!-- Installing the Gis plugin can be done in one of three ways: The GPM (Grav Package Manager) installation method lets you quickly install the plugin with a simple terminal command, the manual method lets you do so via a zip file, and the admin method lets you do so via the Admin Plugin. -->

<!-- ### GPM Installation (Preferred)

To install the plugin via the [GPM](http://learn.getgrav.org/advanced/grav-gpm), through your system's terminal (also called the command line), navigate to the root of your Grav-installation, and enter:

    bin/gpm install gis

This will install the Gis plugin into your `/user/plugins`-directory within Grav. Its files can be found under `/your/site/grav/user/plugins/gis`. -->

### Manual Installation

To install the plugin manually, download the zip-version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `gis`. You can find these files on [GitHub](https://github.com/bricebou/grav-plugin-gis) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/gis
	
> NOTE: This plugin is a modular component for Grav which may require other plugins to operate, please see its [blueprints.yaml-file on GitHub](https://github.com/bricebou/grav-plugin-gis/blob/master/blueprints.yaml).
>
> **Dependencies**
>
> - [Grav Shortcode Core Plugin](https://github.com/getgrav/grav-plugin-shortcode-core)

<!-- ### Admin Plugin

If you use the Admin Plugin, you can install the plugin directly by browsing the `Plugins`-menu and clicking on the `Add` button. -->

## Configuration

Before configuring this plugin, you should copy the `user/plugins/gis/gis.yaml` to `user/config/plugins/gis.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true              #
private:                   # Private Area
  load: true               # Loading Leaflet library inside the Private Area
  height: 340              # Height of the private area maps
  center: '51.505, -0.093' # Default coordinates for centering private area maps
  zoom: 13                 # Default zoom for private area maps
public:                    # Frontend
  load: true               # Loading Leaflet library on the frontend
  height: 340              # Default height for frontend maps
  center: '51.505, -0.093' # Default coordinates for centering frontend maps
  zoom: 13                 # Default zoom for frontend maps

```

Note that if you use the Admin Plugin, a file with your configuration named gis.yaml will be saved in the `user/config/plugins/`-folder once the configuration is saved in the Admin.

## Usage

### For editors

The plugin provides two features.
First, you can geolocate your page: the plugin provide a Gis page, based on the default one, in which you can add several coordinates to your page frontmatter through interactive maps. These coordinates are then displayed on your site frontend through the `{{ gis() }}` Twig function (see below).

The second allows you to display interactive maps into your content: the plugin provides the `[gis /]` shortcode you can use in your content to display interactive maps, with or without markers. For example:

```
[gis /]
```

will display a simple map centered and zoomed accordingly to the plugin settings. You can pass several arguments as map height, center coordinates, zoom level:

```
[gis height=220 zoom=9 center=34.23,1.43 /]
```

You can add some markers to your map, using multiple `markerN` arguments:

```
[gis height=320 marker1="'Test 1', 43, 2, pink" marker2="'Test 2', 44, 1, orange" /]
```

The displayed maps are automatically centered and zoomed to fit all markers.

### For developers

#### **Inside your templates**

The plugin provides the `{{ gis() }}` Twig function. Without parameters, it displays a map (which height is taken from the plugin settings) and populate it with markers, based on the page frontmatter. If there isn't any marker associated to the page, the map is centered and zoomd based on the plugin configuration.

You can specify the height of the map :

```twig
{{ gis({'height': 320}) }}
```

You can prevent the map from being populated with markers:

```twig
{{ gis({'markers': false}) }}
```

You can also pass an array of markers:

```twig
{{ gis({'height': 240, 'markers': [{'name': 'Test', 'icon': 'pink', 'latitude': '51.505', 'longitude': '-0.093'},{'name': 'Test2', 'icon': 'orange', 'latitude': '51', 'longitude': '-0.1'}]}) }}
```

#### **Adding geolocation to page blueprints**

As said above, the GIS plugin provides a Gis page blueprint that simply adds a Geolocation tab.

You can add this feature in your own page blueprints; for example, to add a tab:

```yaml
geolocation:
  type: tab
  title: PLUGIN_GIS.GEOLOCATION
  import@:
    type: partials/gis
```

### Available markers

| blue 	 | green   | orange   | pink   | purple   | red   | teal   | yellow   |
|:------:|:-------:|:--------:|:------:|:--------:|:-----:|:------:|:--------:|
| ![](assets/images/marker-blue.png) | ![](assets/images/marker-green.png) | ![](assets/images/marker-orange.png) | ![](assets/images/marker-pink.png) | ![](assets/images/marker-purple.png) | ![](assets/images/marker-red.png) | ![](assets/images/marker-teal.png) | ![](assets/images/marker-yellow.png) |


## Credits

- [Leaflet javascript library](https://leafletjs.com/)
- [Grav Leaflet Plugin](https://github.com/magikcypress/grav-plugin-leaflet)
- [Map Leaflet Plugin](https://github.com/finanalyst/grav-plugin-map-marker-leaflet)