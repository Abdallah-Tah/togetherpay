buildscript {
    repositories {
        google()
        mavenCentral()
    }
}

import org.gradle.api.JavaVersion
import org.gradle.api.tasks.compile.JavaCompile

allprojects {
    repositories {
        google()
        mavenCentral()
    }
}

val newBuildDir: Directory =
    rootProject.layout.buildDirectory
        .dir("../../build")
        .get()
rootProject.layout.buildDirectory.value(newBuildDir)

subprojects {
    val newSubprojectBuildDir: Directory = newBuildDir.dir(project.name)
    project.layout.buildDirectory.value(newSubprojectBuildDir)
}
subprojects {
    project.evaluationDependsOn(":app")

    // Some older Flutter plugins (e.g., flutter_app_badger) hardcode very low
    // compileSdkVersion, which breaks resource linking on modern Android.
    // Force all Android subprojects to use the app's compileSdk.
    fun forceCompileSdk(androidExt: Any?) {
        if (androidExt == null) return
        val targetSdk = 36
        val targetSdkString = targetSdk.toString()

        // Try multiple setter shapes to support old/new AGP.
        val candidates: List<Pair<String, Any>> = listOf(
            "setCompileSdkVersion" to targetSdk,
            "setCompileSdkVersion" to targetSdkString,
            "setCompileSdk" to targetSdk,
            "setCompileSdk" to targetSdkString,
        )

        for ((method, arg) in candidates) {
            try {
                androidExt.javaClass.methods
                    .firstOrNull { it.name == method && it.parameterTypes.size == 1 }
                    ?.invoke(androidExt, arg)
                return
            } catch (_: Throwable) {
                // keep trying
            }
        }
    }

    fun applyForce() = forceCompileSdk(extensions.findByName("android"))

    pluginManager.withPlugin("com.android.library") { applyForce() }
    pluginManager.withPlugin("com.android.application") { applyForce() }

    // Some transitive Android dependencies still set `-source/-target 1.8`,
    // which triggers warnings on newer JDKs. Standardize on Java 17 for all
    // subprojects and suppress the obsolete-options lint noise.
    tasks.withType<JavaCompile>().configureEach {
        sourceCompatibility = JavaVersion.VERSION_17.toString()
        targetCompatibility = JavaVersion.VERSION_17.toString()
        options.compilerArgs.add("-Xlint:-options")
    }
}

tasks.register<Delete>("clean") {
    delete(rootProject.layout.buildDirectory)
}
