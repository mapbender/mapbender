class CsvExport {
    constructor(settings) {
        this.settings = settings || {};
    }

    export(features, headers) {
        let csvFile = '';

        // header columns export in first row
        csvFile += this.createRow(Object.values(headers));

        for (let i = 0; i < features.length; ++i) {
            const feature = features[i];
            const row = [];
            const props = Mapbender.mapEngine.getFeatureProperties(feature);
            Object.keys(headers).map(function (header) {
                row.push(props[header]);
            });
            csvFile += this.createRow(row);
        }

        const filename = this.settings.filename || 'download.csv';
        Mapbender.FileUtil.downloadFile(csvFile, filename, 'text/csv;charset=utf-8;');
    }

    createRow(rowData) {
        let finalVal = '';
        for (let colIndex = 0; colIndex < rowData.length; colIndex++) {
            let colValue = rowData[colIndex] === null ? '' : rowData[colIndex].toString();
            if (rowData[colIndex] instanceof Date) {
                colValue = rowData[colIndex].toLocaleString();
            }

            if (!colValue.length) {
                colValue = '""';
            } else {
                colValue = '"' + colValue + '"';
            }

            if (colIndex > 0)
                finalVal += ',';
            finalVal += colValue;
        }
        return finalVal + '\n';
    };
}
