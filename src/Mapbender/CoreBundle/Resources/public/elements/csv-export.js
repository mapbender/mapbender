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

        this.downloadAsFile(csvFile, this.settings.filename || 'download.csv')
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

    downloadAsFile(fileContents, filename) {
        const blob = new Blob([fileContents], {type: 'text/csv;charset=utf-8;'});
        const link = document.createElement("a");
        if (link.download !== undefined) { // feature detection
            // Browsers that support HTML5 download attribute
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
}
