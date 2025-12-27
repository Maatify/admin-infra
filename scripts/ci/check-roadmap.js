#!/usr/bin/env node

const fs = require("fs");

const roadmapPath = "docs/architecture/IMPLEMENTATION_ROADMAP.json";

if (!fs.existsSync(roadmapPath)) {
    console.error("❌ Roadmap file not found");
    process.exit(1);
}

const roadmap = JSON.parse(fs.readFileSync(roadmapPath, "utf8"));

if (roadmap.status !== "READY_FOR_EXECUTION") {
    console.error("❌ Roadmap status is not READY_FOR_EXECUTION");
    process.exit(1);
}

const phases = roadmap.phases.sort((a, b) => a.order - b.order);

// Rule A — sequential order
phases.forEach((phase, index) => {
    if (phase.order !== index) {
        console.error(`❌ Phase order mismatch at ${phase.id}`);
        process.exit(1);
    }
});

// Rule C — integrity
phases.forEach((phase) => {
    ["id", "title", "order", "scope", "deliverables"].forEach((field) => {
        if (phase[field] === undefined) {
            console.error(`❌ Phase ${phase.id} missing field: ${field}`);
            process.exit(1);
        }
    });
});

// Rule B — contracts before use
const availableContracts = new Set();

phases.forEach((phase) => {
    phase.deliverables.forEach((d) => {
        if (d.endsWith("Interface")) {
            availableContracts.add(d);
        }
    });

    const usesAudit = JSON.stringify(phase).toLowerCase().includes("audit");
    if (usesAudit && ![...availableContracts].some(c => c.includes("Audit"))) {
        console.error(`❌ Phase ${phase.id} uses audit before AuditLoggerInterface`);
        process.exit(1);
    }
});

console.log("✅ Roadmap governance check passed");
