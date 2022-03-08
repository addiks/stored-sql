const path = require('path');

module.exports = {
  mode: 'development',
  entry: './public/js/lib/storedsql.js',
  module: {
    rules: [
      {
        test: /\.tsx?$/,
        use: 'ts-loader',
        exclude: /node_modules/,
      },
      {
        test: /\.twig$/,
        use: 'twig-loader',
      },
    ],
  },
  resolve: {
    extensions: ['.tsx', '.ts', '.js'],
    modules: ['node_modules', 'ts']
  },
  output: {
    filename: 'js/storedsql.bundle.js',
    path: path.resolve(__dirname, 'public'),
    library: 'storedsql',
    libraryTarget: 'global'
  },
  devtool: 'eval-source-map',
};
