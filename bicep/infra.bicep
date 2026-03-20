@description('Base name for the project (lowercase, letters/numbers)')
param baseName string = 'blogwriteagent'

@description('Environment: dev or prd')
param environment string = 'dev'

@description('Location for all resources')
param location string = resourceGroup().location

var appName = environment == 'prd' ? baseName : '${baseName}-${environment}'
var planName = '${appName}-plan'

resource appServicePlan 'Microsoft.Web/serverfarms@2023-12-01' = {
  name: planName
  location: location
  kind: 'linux'
  sku: {
    name: 'F1'
    tier: 'Free'
  }
  properties: {
    reserved: true
  }
}

resource webApp 'Microsoft.Web/sites@2023-12-01' = {
  name: appName
  location: location
  kind: 'app,linux'
  properties: {
    serverFarmId: appServicePlan.id
    httpsOnly: true
    siteConfig: {
      linuxFxVersion: 'PHP|8.2'
      alwaysOn: true
      appCommandLine: ''
    }
  }
}

output webAppName string = webApp.name
output webAppHostname string = webApp.properties.defaultHostName
